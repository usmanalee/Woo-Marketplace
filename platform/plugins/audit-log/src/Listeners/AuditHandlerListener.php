<?php

namespace Woo\AuditLog\Listeners;

use Woo\AuditLog\Events\AuditHandlerEvent;
use Woo\AuditLog\Repositories\Interfaces\AuditLogInterface;
use Exception;
use Illuminate\Http\Request;

class AuditHandlerListener
{
    public AuditLogInterface $auditLogRepository;

    protected Request $request;

    public function __construct(AuditLogInterface $auditLogRepository, Request $request)
    {
        $this->auditLogRepository = $auditLogRepository;
        $this->request = $request;
    }

    public function handle(AuditHandlerEvent $event): void
    {
        try {
            $data = [
                'user_agent' => $this->request->userAgent(),
                'ip_address' => $this->request->ip(),
                'module' => $event->module,
                'action' => $event->action,
                'user_id' => $this->request->user() ? $this->request->user()->getKey() : 0,
                'reference_user' => $event->referenceUser,
                'reference_id' => $event->referenceId,
                'reference_name' => $event->referenceName,
                'type' => $event->type,
            ];

            if (! in_array($event->action, ['loggedin', 'password'])) {
                $data['request'] = json_encode($this->request->except([
                    'username',
                    'password',
                    're_password',
                    'new_password',
                    'current_password',
                    'password_confirmation',
                    '_token',
                    'token',
                    'refresh_token',
                    'remember_token',
                ]));
            }

            $this->auditLogRepository->createOrUpdate($data);
        } catch (Exception $exception) {
            info($exception->getMessage());
        }
    }
}