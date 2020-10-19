<?php
/**
 * The Index Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Base extends Controller_Template
{
    protected $data = array();

    protected $header_link = true;

    public function before() {
/****** メンテナンス表示転送用処理 スタート ******
		// メンテナンス中画面への転送処理
		$url = Uri::current();

		// アクセス許可IPの設定
		$permit_ip = [];
		$permit_ip[] = "210.254.134.50";	// isobar
		$permit_ip[] = "113.43.34.163";		// isobar
		$permit_ip[] = "119.245.154.154";	// ISAO
		$permit_ip[] = "58.158.41.226";		// TGL
		$permit_ip[] = "210.251.245.129";	// TGL
        $permit_ip[] = "210.254.134.38";	// ddhgrp-drf-1
        //$permit_ip[] = "182.10.0.1";      // local

		// ホスト名の取得(https://から取得[末尾は「/」無し])
		$hostname = Config::get('hostname');
		$mainte_url = $hostname."/maintenance/";
		$access_ip = $_SERVER['REMOTE_ADDR'];
		$access_permit_flg = false;					// 許可・非許可フラグ

		// アクセス元のIP,経由後IPが飛んできた場合に分割する
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$access_ip_array = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
			$access_ip = $access_ip_array[0];	// アクセス元IPの取得
		}

		foreach((array) $permit_ip as $key1 => $val1) {
			if($val1 == $access_ip) {
				$access_permit_flg = true;
			}
		}

		if(stristr($url, 'maintenance') === FALSE && $access_permit_flg == false) {
			Response::redirect($mainte_url, 'refresh');
        }
****** メンテナンス表示転送用処理 エンド ******/
        if (!empty($this->template) and is_string($this->template)) {
            // Load the template
            $this->template = \View::forge($this->template);
        }

        return parent::before();
    }

}
