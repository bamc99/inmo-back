<?php

namespace App\Jobs;

use App\Mail\EmailBase;
use App\Mail\EmailParams;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mailable;

    private string $toEmail;

    /**
     * Create a new job instance.
     *
     * @param string $toEmail (who's receiver)
     *
     * @return void
     */
    // public function __construct(string $htmlFile, array $data, string $fromEmail, string $fromName, string $subjectText, string $toEmail)
    public function __construct( string $toEmail, Mailable $mailable)
    {
        $this->mailable = $mailable;
        $this->toEmail = $toEmail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->toEmail)->send($this->mailable);
    }
}
