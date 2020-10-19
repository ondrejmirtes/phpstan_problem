<?php

use \Model\PasswordReminder;

class Controller_Password extends Controller_Base
{
    /**
     * INDEX
     *
     * @access  public
     * @return  Response
     */
    public function action_reminder()
    {
        if (Input::method()=='POST') {
            $val = Validation::forge();
            // バリデーションチェック
            $val->add('mail', 'メールアドレス')->add_rule('required');
            $val->add('date_y', '年')->add_rule('required');
            $val->add('date_m', '月')->add_rule('required');
            $val->add('date_d', '日')->add_rule('required');
            
            // 時刻
            $date = date(Config::get('date_format'));
            $expireDate = date(Config::get('date_format'), strtotime('1 day'));

            // バリデーションチェック用受け渡しパラメータ
            $this->data['val'] = $val;

            //成功
            if ($val->run()) {
                $birthDate = sprintf('%04d/%02d/%02d', Input::post('date_y'), Input::post('date_m'), Input::post('date_d'));
                // 認証キー取得APIのエンドポイント取得
                $url = sprintf(Config::get("apiserver"), 'auth', 'RemainderAuthKeyBirth');
                $params = array();
                
                Log::debug('url', $url);
                
                // リクエストパラメータ
                $params['id'] = Input::post('mail');
                $params['mailAddress'] = Input::post('mail');
                $params['birthDate'] = $birthDate;
                $params['subSystemKbn'] = Config::get('sub_system'); // 固定
                
                // リマインド一時キー取得APIを呼び出し
                $output= CURL::curlset($url, true, $params, false, false, true, 'cookie', 'tmp', true);
                $json = json_decode($output);
                
                Log::debug(print_r($json, true));
                
                // レスポンス結果
                $returnCd = $json->returnCd;
                
                // 認証キー取得API結果による分岐処理(正常系以外)
                if ($returnCd == 99) {
                    Log::error(
                        sprintf(
                            '{"id":"%s","birthDate":"%s","subSystemKbn":"%s","action":"%s","entry_at":"%s","%s":"%s"}',
                            Input::post("mail"),
                            $birthDate,
                            Config::get('sub_system'),
                            'password_reminder',
                            $date,
                            'RemainderAuthKeyBirth returnCd',
                            $returnCd
                        )
                    );
                    
                    // システムエラー画面にリダイレクト
                    Response::redirect(Config::get('system_error'));
                } elseif ($returnCd == 10 || $returnCd == 13) {
                    Log::error(
                        sprintf(
                            '{"id":"%s","birthDate":"%s","subSystemKbn":"%s","action":"%s","entry_at":"%s","%s":"%s"}',
                            Input::post("mail"),
                            $birthDate,
                            Config::get('sub_system'),
                            'password_reminder',
                            $date,
                            'RemainderAuthKeyBirth returnCd',
                            $returnCd
                        )
                    );
                    // 完了画面にリダイレクト
                    return View::forge('password/reminder_complete');
                    
                    // login.htmlで表示させるエラーパラメータ
                    if ($returnCd == 1) {
                        $this->data['input_error'] = 'input_error';
                    }
                    
                    // パスワードリマインダー画面にエラーを表示して遷移させる
                    return View::forge('password/reminder', $this->data);
                }
                
                Log::debug('RemainderAuthKeyBirth returnCd:'.$returnCd);
                
                $temporarykey = $json->temporarykey;
                
                $debug_log = [];
                $debug_log[] = sprintf(
                    '{"cp_id":"%s","glico_members_id":"%s","subSystemKbn":"%s","temporarykey":"%s","action":"%s","entry_at":"%s","%s":"%s"}',
                    Session::get('cp_id'),
                    $params['id'],
                    Config::get('sub_system'),
                    $json->temporarykey,
                    'password_reminder',
                    $date,
                    'RemainderAuthKeyBirth returnCd',
                    $json->returnCd
                );
                //パスワードリマインダーテーブルに登録
                Log::debug('DB_password_reminder_Insert_start');
                
                $password_reminder = new Model_PasswordReminder();
                $password_reminder::forge();
                $password_reminder->mail_address = Input::post('mail');
                $password_reminder->temporarykey = $temporarykey;
                $password_reminder->created_at = $date;
                $password_reminder->expired_at = $expireDate;
                $password_reminder->save();
                
                Log::debug('DB_password_reminder_Insert_end');
                
                // メール送信
                try {
                    $body = sprintf('http://52.199.62.117/password/resetting?temporarykey=%s', $temporarykey);
                    Log::debug($body, 'mail_body');
                    Log::debug(Input::post("mail"), 'mail_body');
                    Log::debug($temporarykey, 'temporarykey');
                    Log::debug('mail_send');
                    $factory = Email::forge();
                    $factory->from(Config::get('noreplyemail.address'), Config::get('noreplyemail.name'));
                    $factory->to($password_reminder->mail_address);
                    $factory->subject(Config::get('resetting_password_email_title'));
                    $factory->body(sprintf(Config::get('resetting_password_mail_body'), $body));
                    $factory->send();
                    Log::debug('reset_password');
                } catch (Exception $e) {
                    // エラー画面にリダイレクト
                    Log::warning('mail_send error : entry275 :'.$e);
                    // システムエラー画面にリダイレクト
                    Response::redirect(Config::get('system_error'));
                }
                
                
                
                return View::forge('password/reminder_complete');
            }
        }

        return View::forge('password/reminder');
    }
    
    /**
     * mail_confirm
     *
     * @access  public
     * @return  Response
     */
    public function action_resetting()
    {
        $id = Input::get('id');
        $temporarykey = Input::get('temporarykey');
        Log::debug($id, 'Redirect_from_mail');
        
        // 時刻
        $date = date(Config::get('date_format'));
        
        // password_reminderテーブルのレコードを取得
        $password_reminder = Model_PasswordReminder::findByTemporarykey($temporarykey, $date);//
        
        
        // mail_authorizationテーブルのレコードが取得できなかった場合エラー画面へ遷移
        if (!$password_reminder) {
            // エラー画面にリダイレクト
            Log::warning('password_remindertable Empty : ');
            // システムエラー画面にリダイレクト
            Response::redirect(Config::get('system_error'));
        }
        
        
        
        if (Input::method()=='POST') {
            // バリデーションチェック
            $val = Validation::forge();
            $val->add('pass', 'パスワード1')->add_rule('required');
            $val->add('pass2', 'パスワード2')->add_rule('required');
            
            //成功
            if ($val->run()) {

                // パスワード変更APIのエンドポイント取得
                $url = sprintf(Config::get('apiserver'), 'auth', 'ModPassword');
                $params = array();

                // リクエストパラメータ
                $params['id'] = $password_reminder->mail_address;
                $params['temporarykey'] = $password_reminder->temporarykey;
                $params['newPassword'] = Input::post("pass");
                // パスワード変更APIの呼び出し
                $output= CURL::curlset($url, true, $params, false, false, true, 'cookie', 'tmp', true);
                $json = json_decode($output);

                Log::debug(print_r($json, true));

                $returnCd = $json->returnCd;

                //returnCdによる処理の分岐
                if ($returnCd == 1 ||$returnCd == 21 || $returnCd == 99) {
                    Log::error(
                        sprintf(
                            '{"mail_address":"%s,"temporarykey":"%s","newPassword":"%s","action":"%s","entry_at":"%s","%s":"%s"}',
                            $password_reminder->mail_address,
                            $password_reminder->temporarykey,
                            Input::post('pass'),
                            'resetting password',
                            $date,
                            'ModPassword returnCd',
                            $returnCd
                        )
                    );

                    // システムエラー画面にリダイレクト
                    Response::redirect(Config::get('system_error'));
                } elseif ($returnCd == 8) {
                    Log::error(
                        sprintf(
                            '{"mail_address":"%s,"temporarykey":"%s","newPassword":"%s","action":"%s","entry_at":"%s","%s":"%s"}',
                            $password_reminder->mail_address,
                            $password_reminder->temporarykey,
                            Input::post('pass'),
                            'resetting password',
                            $date,
                            'ModPassword returnCd',
                            $returnCd
                        )
                    );
                    
                    
                    // システムエラー画面にリダイレクト
                    Response::redirect(Config::get('system_error'));
                }
                
                Log::debug('ModPassword returnCd:'.$returnCd);
                
                // メール送信
                try {
                    Log::debug('mail_send');
                    $factory = Email::forge();
                    $factory->from(Config::get('noreplyemail.address'), Config::get('noreplyemail.name'));
                    $factory->to($password_reminder->mail_address);
                    $factory->subject(Config::get('finish_resetting_password_email_title'));
                    $factory->body(Config::get('finish_resetting_password_mail_body'));
                    $factory->send();
                    Log::debug('finish_resetting_password');
                } catch (Exception $e) {
                    // エラー画面にリダイレクト
                    Log::warning('mail_send error : password259 :'.$e);
                    // システムエラー画面にリダイレクト
                    Response::redirect(Config::get('system_error'));
                }
                
                // Model_PasswordReminder::deleteByID($password_reminder->id);
                
                //確認画面にリダイレクト
                Log::debug('resetting_password_OK');
                return View::forge('password/resetting_complete');
            }
        }
        
        return View::forge('password/resetting');
    }
}
