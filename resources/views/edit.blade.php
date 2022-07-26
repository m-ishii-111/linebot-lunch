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
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            {{ __('登録に失敗しました。') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('store') }}">
                        @csrf
                        @foreach($messages as $type => $data)
                            @foreach($data as $seq => $text)
                                <?php
                                    $name = $type . '-' . $seq;
                                    $value = $errors->has($name) ? old($name) : $text;
                                ?>
                                <div class="row mb-3">
                                    <label for="{{ __($name) }}" class="col-md-4 col-form-label text-md-end">{{ __($name) }}</label>

                                    <div class="col-md-6">
                                        <textarea class="form-control @error($name) is-invalid @enderror" name="{{ __($name) }}" rows="3" required autofocus>{{ $value }}</textarea>

                                        @error($name)
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach
                        @endforeach

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
