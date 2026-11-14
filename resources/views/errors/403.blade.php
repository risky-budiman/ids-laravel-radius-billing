@extends('errors.layout')
@php
    $raw = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $generic = ['', 'Forbidden', 'This action is unauthorized.', 'Unauthorized: You do not have the required permissions to access this area.'];
    $msg = in_array($raw, $generic, true)
        ? 'Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator bila Anda memerlukan akses.'
        : ($raw === 'Your account has been deactivated. Please contact the administrator.' ? 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.' : $raw);
@endphp
@section('code', '403')
@section('title', 'Akses ditolak')
@section('message', $msg)
@section('icon', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z')
