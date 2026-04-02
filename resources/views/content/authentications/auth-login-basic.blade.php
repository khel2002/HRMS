@extends('layouts/blankLayout')

@section('title', 'Login')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<style>
  
    .auth-page-bg {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    .auth-card {
        display: flex;
        width: 100%;
        max-width: 900px;
        min-height: 500px;
        border-radius: 10px 0 0 10px;
        box-shadow: 0 8px 40px rgba(100, 130, 200, 0.18);
        background: #fff;
        text-align: center;
    }
    .auth-form-side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 3rem 3rem;
        background: #fff;
        border-right: 1px solid #c0c0c4;
    }


    .auth-image-side {
      
       display: flex;
       align-items: center;
    }

    .auth-image-side img {
        width: 450px !important;
        height: 300px;
        object-position: center;
        display: block;
    }
/* 
    @media (min-width: 768px) {
        .auth-image-side img {
            width: 400px !important;
            height: 300px;
            object-position: center;
            display: block;
        }
    } */

    @media (max-width: 767.98px) {
        .auth-card {
            flex-direction: column;
            max-width: 480px;
        }

        .auth-image-side {
         width: 100%;
        }

        .auth-form-side {
            padding: 2rem 2.5rem;
             border: none;
        }
    }

    @media (max-width: 479.98px) {
        .auth-form-side {
            padding: 1.75rem 1.25rem;
             border: none;
        }
       
    }
</style>
@endsection

@section('content')
<div class="auth-page-bg">
    <div class="auth-card">
        <div class="auth-form-side">
            <div class="app-brand mb-4 d-lg-none d-md-none" style="display:flex; justify-content:center">
                 <img src="{{ asset('assets/img/logo/HRIS-LOGO.png') }}" alt="Logo" style="width: 50px; height: auto;">
            </div>

            <h4 class="mb-1">Welcome to {{ config('variables.templateName') }}!</h4>
            <p class="mb-5 text-muted">Please sign-in to your account using your username.</p>
            <form id="formAuthentication" class="mb-4" action="{{ route('auth.login') }}" method="POST">
                @csrf
                <div class="form-floating form-floating-outline mb-4 form-control-validation">
                    <input
                        type="text"
                        class="form-control @error('username') is-invalid @enderror"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        value="{{ old('username') }}"
                        autofocus
                    />
                    <label for="username">Username</label>
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-5 form-control-validation">
                    <div class="form-password-toggle">
                        <div class="input-group input-group-merge">
                            <div class="form-floating form-floating-outline">
                                <input
                                    type="password"
                                    id="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    name="password"
                                    placeholder="············"
                                    aria-describedby="password"
                                />
                                <label for="password">Password</label>
                            </div>
                            <span class="input-group-text cursor-pointer">
                                <i class="icon-base ri ri-eye-off-line icon-20px"></i>
                            </span>
                        </div>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
                </div>
            </form>

            {{-- <p class="text-center mb-0">
                <span>New on our platform? </span>
                <a href="{{ url('auth/register-basic') }}">Create an account</a>
            </p> --}}

        </div>

        <div class="auth-image-side d-none d-md-flex d-lg-flex">
            <img src="{{ asset('assets/img/backgrounds/auth-bg-remove.png') }}" alt="Auth Background">
        </div>

    </div>
</div>
@endsection