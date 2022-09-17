<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Mailer extends Mailable
{
    use Queueable, SerializesModels;

    public $subjects;
    public $body;
    public $menus;
    public $attachments;
    public $blade;
    public $data;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject='',$body='',$attachments=array(),$blade='default',$data=array(),$menus=array())
    {
        $this->subjects = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
        $this->blade = $blade;
        $this->data = $data;
        $this->menus = $menus;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject($this->subjects);
        if(!empty($this->attachments))
        {
            $raw = false;
            $attach = false;
            foreach($this->attachments as $attachment)
            {
                if(stripos($attachment['path'], 'http') === FALSE)
                {
                    $this->attach($attachment['path'], [
                        'as' => $attachment['title'],
                        'mime' => $attachment['mediatype'],
                    ]);
                    $attach = true;
                }
                else
                {
                    $path = file_get_contents($attachment['path']);
                    $this->attachData($path,$attachment['title'],[
                        'mime' => $attachment['mediatype']
                    ]);
                    $raw = true;
                }
            }

            if(!$attach) $this->attachments = array();
        }

        return $this->view('emails.'.$this->blade);
    }
}
