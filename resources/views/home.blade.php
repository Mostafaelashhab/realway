@extends('layout')
@section('title', 'EgTrain — مواعيد قطارات مصر')

@section('content')
<div class="hero">
    <h1>رايح فين؟</h1>
    <p>المواعيد والأسعار والكراسي الفاضية — في ثانية.</p>
</div>

<div class="tabs">
    <input type="radio" name="tab" id="t1" checked>
    <input type="radio" name="tab" id="t2">

    <div class="tabbar">
        <label for="t1">بالمحطات</label>
        <label for="t2">برقم القطر</label>
    </div>

    <form class="card panel p1" action="{{ route('search') }}" method="GET">
        <div class="two">
            <div class="field">
                <label class="lbl" for="from">من</label>
                <select name="from" id="from" required>
                    <option value="">اختار محطة</option>
                    @foreach ($stations as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="lbl" for="to">إلى</label>
                <select name="to" id="to" required>
                    <option value="">اختار محطة</option>
                    @foreach ($stations as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="field">
            <label class="lbl" for="d1">التاريخ</label>
            <input type="date" name="date" id="d1" value="{{ $date }}" min="{{ $date }}">
        </div>
        <button type="submit">ابحث</button>
    </form>

    <form class="card panel p2" action="{{ route('train') }}" method="GET">
        <div class="field">
            <label class="lbl" for="number">رقم القطر</label>
            <input type="text" name="number" id="number" inputmode="numeric" placeholder="903" required>
        </div>
        <div class="field">
            <label class="lbl" for="d2">التاريخ</label>
            <input type="date" name="date" id="d2" value="{{ $date }}" min="{{ $date }}">
        </div>
        <button type="submit">ابحث</button>
    </form>
</div>
@endsection
