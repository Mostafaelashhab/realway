@extends('layout')
@section('title', 'EgTrain — مواعيد قطارات مصر')

@section('content')
<div class="hero">
    <h1>رايح فين؟</h1>
    <p>مواعيد قطارات مصر وأسعارها — في ثانية.</p>
</div>

@include('_search')

@if ($devAvailable)
    <div class="dev-row">
        @if ($dev)
            <span class="dev-on">وضع المطوّر مفعّل</span>
            <form method="POST" action="{{ route('dev.lock') }}">
                @csrf
                <button type="submit" class="dev-link">اقفله</button>
            </form>
        @else
            <a class="dev-link" href="#dev">كن مطوّر</a>
        @endif
    </div>

    <div class="modal" id="dev">
        <a class="modal-back" href="#" aria-label="اقفل"></a>
        <form class="card modal-card" method="POST" action="{{ route('dev.unlock') }}">
            @csrf
            <h2 class="modal-title">وضع المطوّر</h2>
            <p class="modal-text">اكتب المفتاح عشان تفتح البيانات الإضافية. المتصفح هيفتكره ومش هيسألك تاني.</p>

            @if (session('devError'))
                <p class="modal-err">{{ session('devError') }}</p>
            @endif

            <div class="field">
                <label class="lbl" for="key">المفتاح</label>
                <input type="password" name="key" id="key" autocomplete="off" required autofocus>
            </div>

            <button type="submit">افتح</button>
            <a class="modal-cancel" href="#">إلغاء</a>
        </form>
    </div>
@endif
@endsection
