<?php

namespace Irnoti\Sdk\Resources;

/**
 * Phonebook groups and contacts. Read: `phonebook.read`; write: `phonebook.write`.
 */
class PhonebookResource extends Resource
{
    /**
     * @return array<int, array{id: string, name: string, contacts: int, createdAt: string}>
     */
    public function groups(): array
    {
        return $this->client->request('get', '/phonebook/groups');
    }

    /**
     * Group names are unique (409 on duplicates).
     *
     * @return array{id: string, name: string}
     */
    public function createGroup(string $name): array
    {
        return $this->client->request('post', '/phonebook/groups', ['name' => $name]);
    }

    public function deleteGroup(string|int $id): void
    {
        $this->client->request('delete', '/phonebook/groups/' . rawurlencode((string) $id));
    }

    /**
     * Cursor-paged: pass `after` = id of the last contact of the previous page.
     *
     * @return array<int|string, mixed>
     */
    public function contacts(string|int $groupId, ?int $take = null, string|int|null $after = null, ?string $q = null): array
    {
        return $this->client->request('get', '/phonebook/groups/' . rawurlencode((string) $groupId) . '/contacts', $this->filter([
            'take' => $take,
            'after' => $after === null ? null : (string) $after,
            'q' => $q,
        ]));
    }

    /**
     * Add up to 10,000 contacts per call; invalid and duplicate numbers are counted, not fatal.
     *
     * @param  array<int, array{mobile: string, name?: string, lastName?: string, company?: string, email?: string, city?: string, note?: string, birth?: string, fields?: array<string, string>}>  $contacts
     * @return array{added: int, duplicates: int, invalid: int}
     */
    public function addContacts(string|int $groupId, array $contacts): array
    {
        return $this->client->request('post', '/phonebook/groups/' . rawurlencode((string) $groupId) . '/contacts', ['contacts' => $contacts]);
    }

    /**
     * Import a CSV/TXT body (one `mobile[,name]` per line, max 5 MB).
     *
     * @return array{added: int, duplicates: int, invalid: int}
     */
    public function importCsv(string|int $groupId, string $contents, string $filename = 'contacts.csv'): array
    {
        return $this->client->upload('/phonebook/groups/' . rawurlencode((string) $groupId) . '/import', 'file', $contents, $filename);
    }

    /**
     * @param  array<string, mixed>  $attributes  mobile, name, lastName, company, email, city, note, birth, fields, optedOut.
     * @return array<string, mixed>
     */
    public function updateContact(string|int $id, array $attributes): array
    {
        return $this->client->request('patch', '/phonebook/contacts/' . rawurlencode((string) $id), $attributes);
    }

    public function deleteContact(string|int $id): void
    {
        $this->client->request('delete', '/phonebook/contacts/' . rawurlencode((string) $id));
    }
}
