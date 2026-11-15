<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Tampilan & pesan aplikasi berbahasa Indonesia secara default.
 */
class IndonesianLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_indonesian(): void
    {
        $this->assertSame('id', app()->getLocale());
    }

    public function test_validation_messages_are_indonesian_with_translated_field_names(): void
    {
        $v = Validator::make(['phone' => '', 'email' => 'bukan-email'], ['phone' => 'required', 'email' => 'email']);

        $this->assertSame('Nomor HP wajib diisi.', $v->errors()->first('phone'));
        $this->assertSame('Email harus alamat email yang valid.', $v->errors()->first('email'));
    }

    public function test_pagination_and_common_labels_are_indonesian(): void
    {
        $this->assertSame('Menampilkan', __('Showing'));
        $this->assertSame('Batal', __('Cancel'));
        $this->assertSame('Email/username atau kata sandi salah.', __('auth.failed'));
    }

    public function test_failed_login_message_is_indonesian(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->from(route('login'))->post(route('login'), ['login' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['login' => 'Email/username atau kata sandi salah.']);
    }
}
