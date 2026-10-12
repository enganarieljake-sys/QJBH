<?php

namespace Tests\Feature;

use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MailTypographyTest extends TestCase
{
    public function test_notification_emails_use_the_nunito_mail_theme(): void
    {
        $html = (string) (new MailMessage)
            ->greeting('Hello')
            ->line('Typography check')
            ->render();

        $this->assertSame('qjbh', config('mail.markdown.theme'));
        $this->assertStringContainsString("font-family: 'Nunito'", $html);
    }

    public function test_tenant_invitation_declares_nunito(): void
    {
        $template = file_get_contents(resource_path('views/emails/tenant-invitation.blade.php'));

        $this->assertStringContainsString("font-family:'Nunito'", $template);
        $this->assertStringContainsString('family=Nunito', $template);
    }
}
