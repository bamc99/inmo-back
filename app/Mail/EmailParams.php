<?php

namespace App\Mail;

class EmailParams
{
    public string $htmlFile;
    public array $data;
    public string $fromEmail;
    public string $fromName;
    public string $subjectText;

    public function __construct(
        string $htmlFile,
        array $data,
        string $fromEmail,
        string $fromName,
        string $subjectText,
    ) {
        $this->htmlFile = $htmlFile;
        $this->data = $data;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->subjectText = $subjectText;
    }
}
