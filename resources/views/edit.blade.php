<?php
    $follow = $errors->has('follow') ? old('follow') : $follow;
    $location = $errors->has('location') ? old('location') : $location;
    $location_button = $errors->has('location_button') ? old('location_button') : $location_button;
    $stamp = $errors->has('stamp') ? old('stamp') : $stamp;
    $not_found = $errors->has('not_found') ? old('not_found') : $not_found;
?>

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('メッセージ管理画面') }}</div>

                <div class="card-body">
                    @if (session('flash_message'))
                        <div class="alert alert-success" role="alert">
                            {{ session('flash_message') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('store') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="follow" class="col-md-4 col-form-label text-md-end">{{ __('友達追加＆ブロック解除時') }}</label>

                            <div class="col-md-6">
                                <textarea class="form-control @error('follow') is-invalid @enderror" name="follow" rows="3" required autofocus>{{ $follow }}</textarea>

                                @error('follow')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="location" class="col-md-4 col-form-label text-md-end">{{ __('位置情報送信依頼') }}</label>

                            <div class="col-md-6">
                                <textarea class="form-control @error('location') is-invalid @enderror" name="location" rows="3" required autofocus>{{ $location }}</textarea>

                                @error('location')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="location_button" class="col-md-4 col-form-label text-md-end">{{ __('位置情報送信ボタン') }}</label>

                            <div class="col-md-6">
                                <textarea class="form-control @error('location_button') is-invalid @enderror" name="location_button" rows="3" required autofocus>{{ $location_button }}</textarea>

                                @error('location_button')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="stamp" class="col-md-4 col-form-label text-md-end">{{ __('スタンプ受信時') }}</label>

                            <div class="col-md-6">
                                <textarea class="form-control @error('stamp') is-invalid @enderror" name="stamp" rows="3" required autofocus>{{ $stamp }}</textarea>

                                @error('stamp')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="not_found" class="col-md-4 col-form-label text-md-end">{{ __('情報が見つからない時') }}</label>

                            <div class="col-md-6">
                                <textarea class="form-control @error('not_found') is-invalid @enderror" name="not_found" rows="3" required autofocus>{{ $not_found }}</textarea>

                                @error('not_found')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
