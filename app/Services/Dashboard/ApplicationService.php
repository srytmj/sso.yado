<?php

namespace App\Services\Dashboard;

use App\Models\OAuth\Client;
use App\Services\AuditLogService;
use Illuminate\Support\Collection;
use Laravel\Passport\ClientRepository;

class ApplicationService
{
    public function __construct(
        private readonly ClientRepository $clients,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(): Collection
    {
        return Client::orderByDesc('created_at')->get();
    }

    public function create(array $data): Client
    {
        $client = $this->clients->createAuthorizationCodeGrantClient(
            name: $data['name'],
            redirectUris: [$data['redirect_uri']],
            confidential: true,
        );

        $client->forceFill(['secret_encrypted' => $client->plainSecret])->save();

        $this->auditLog->record('dashboard.application_created', "Aplikasi \"{$data['name']}\" dibuat");

        return $client;
    }

    public function update(Client $client, array $data): void
    {
        $this->clients->update($client, $data['name'], [$data['redirect_uri']]);

        $this->auditLog->record('dashboard.application_updated', "Aplikasi \"{$data['name']}\" diperbarui");
    }

    public function delete(Client $client): void
    {
        foreach ($client->tokens as $token) {
            $token->revoke();
            $token->refreshToken?->update(['revoked' => true]);
        }

        $this->clients->delete($client);

        $this->auditLog->record('dashboard.application_deleted', "Aplikasi \"{$client->name}\" dihapus");
    }
}
