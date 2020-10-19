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

        if (!empty($this->template) and is_string($this->template)) {
            // Load the template
            $this->template = \View::forge($this->template);
        }

        return parent::before();
    }

}
