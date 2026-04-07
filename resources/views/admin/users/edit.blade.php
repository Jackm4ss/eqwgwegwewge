@extends('admin.layouts.app')

@php
  $title = 'Edit Participant';
@endphp

@section('content')
  <div class="row justify-content-center">
    <div class="col-12 col-xxl-10">
      @include('admin.users.partials.editor-panel', [
        'user' => $user,
        'mode' => 'edit',
        'isModal' => false,
        'oldInputEnabled' => true,
        'cancelUrl' => route('admin.users.index'),
        'fieldIdPrefix' => 'page-user-editor-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($user['user_id'] ?? 'user')),
      ])
    </div>
  </div>
@endsection
