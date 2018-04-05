<?php
namespace AppBundle\Entity;

class Message {
    protected $title;
    protected $name;
    protected $email;
    protected $subject;
    protected $content;
    protected $ip;
    protected $goto;

    public function getMailString(){
        $message = "Subject: " . htmlspecialchars($this->subject)."<br /><br />";
        $message .= "From: " . $this->email . " (IP: " . $this->ip .")<br /><br />";
        $message .= "Payload: ". htmlspecialchars($this->content)."<br /><br />";
        $message .= "Date: ".date('d.m.Y H:i:s');

        return $message;
    }

    /**
     * @return mixed
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip) {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject) {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    public function getGoto(){
        return $this->goto;
    }
    public function setGoto(string $goto){
        $this->goto = $goto;
    }
}