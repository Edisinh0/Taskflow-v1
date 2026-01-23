<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class SLAEscalationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Task $task,
        public User $supervisor,
        public int $daysOverdue
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ccAddresses = [];

        // CC al asignado si está configurado
        if (config('sla.email.cc_assignee', true) && $this->task->assignee) {
            $ccAddresses[] = new Address(
                $this->task->assignee->email,
                $this->task->assignee->name
            );
        }

        return new Envelope(
            from: new Address(
                config('sla.email.from.address', config('mail.from.address')),
                config('sla.email.from.name', config('mail.from.name'))
            ),
            subject: "[SLA ESCALADA] Tarea '{$this->task->title}' - {$this->daysOverdue} días atrasada",
            cc: $ccAddresses,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.sla-escalation',
            with: [
                'task' => $this->task,
                'supervisor' => $this->supervisor,
                'daysOverdue' => $this->daysOverdue,
                'flow' => $this->task->flow,
                'assignee' => $this->task->assignee,
                'taskUrl' => $this->getTaskUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate task URL
     */
    private function getTaskUrl(): string
    {
        $baseUrl = config('app.frontend_url', config('app.url'));
        return "{$baseUrl}/flows/{$this->task->flow_id}?task={$this->task->id}";
    }
}
