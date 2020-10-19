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
        

        return View::forge('password/reminder');
    }
    
 
}
