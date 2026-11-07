<?php

use App\Models\User;

test('registration links to public legal documents without leaving the form', function (): void {
    $this->get(route('register'))->assertOk()
        ->assertSee('href="'.route('documents.vop').'" target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('href="'.route('documents.gdpr').'" target="_blank" rel="noopener noreferrer"', false);
});

test('guests can read the legal documents as PDFs', function (string $routeName, string $filename): void {
    $response = $this->get(route($routeName));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename='.$filename);

    expect($response->getContent())->toStartWith('%PDF-');
    $this->assertGuest();
})->with([
    'terms' => ['documents.vop', 'vop.pdf'],
    'privacy' => ['documents.gdpr', 'gdpr.pdf'],
]);

test('unverified customers can read the legal documents', function (string $routeName): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route($routeName))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
})->with(['documents.vop', 'documents.gdpr']);

test('guests still need to log in before reading their rental contract', function (): void {
    $this->get(route('documents.rental'))->assertRedirect(route('login'));
});

test('unverified customers still need to verify before reading their rental contract', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('documents.rental'))
        ->assertRedirect(route('verification.notice'));
});
