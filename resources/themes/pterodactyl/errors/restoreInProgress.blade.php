{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.error')

@section('title')
    Backup In Progress
@endsection

@section('content-header')
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1 col-xs-12">
            <div class="box box-warning">
                <div class="box-body text-center">
                    <h1 class="text-warning" style="font-size: 70px !important;font-weight: 100 !important;color: #F39C12 !important;">Backup Restore In Progress</h1>
                    <p class="text-muted">Backup restore in progress! Please wait...</p>
                </div>
                <div class="box-footer with-border">
                    <a href="{{ URL::previous() }}"><button class="btn btn-warning">&larr; @lang('base.errors.return')</button></a>
                    <a href="/"><button class="btn btn-default">@lang('base.errors.home')</button></a>
                </div>
            </div>
        </div>
    </div>
@endsection
