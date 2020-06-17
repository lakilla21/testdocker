{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    Backup
@endsection

@section('content-header')
    <h1>Backup</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">@lang('strings.home')</a></li>
        <li><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
        <li>@lang('navigation.server.configuration')</li>
        <li class="active">Backup</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title mt-3" style="padding-bottom: 5px;">All Backups</h3>
                    <div class="box-tools">
                        <button data-action="create" class="btn btn-success" type="button">Create New</button>
                    </div>
                </div>
            </div>
        </div>

        @if(!$saves->isEmpty())
            <div id="backups">
                @foreach($saves as $save)
                    <div class="col-xs-12 col-lg-3 col-md-6">
                        <div class="box">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa text-success fa-check-circle"></i> {{$save->name}}
                                </h3>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class=" col-xs-6">
                                        <i class="fa fa-save fa-4x"></i>
                                    </div>
                                    <div class="col-xs-6 align-items-center">
                                        <span title="Date"><i style="color: green;" class="fa fa-clock-o"></i> {{$save->date}}</span><br>
                                        <span title="Original Save Name"><i style="color: orange;" class="fa fa-folder"></i> {{$save->name}}
                                            .zip</span><br>
                                        <span title="Encoded Save Name"><i style="color: red;" class="fa fa-terminal"></i> {{$save->file}}
                                            .zip</span>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <div class="text-center">
                                    <a class="btn btn-success"
                                       href="{{ route('server.backup.download', [$server->uuidShort, $save->id])}}"
                                       title="Download">
                                        <i class="fa fa-cloud-download"></i>
                                    </a>
                                    <a class="btn btn-warning" href="#" data-action="restore"
                                       data-id="{{$save->id}}"
                                       title="Restore">
                                        <i class="fa fa-history"></i>
                                    </a>
                                    <a class="btn btn-danger" href="#" data-action="delete"
                                       data-id="{{$save->id}}"
                                       title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="col-xs-12">
                <div class="alert alert-info alert-dismissable" role="alert">
                    You haven't any backup!
                </div>
            </div>
        @endif

        <div class="col-xs-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title mt-3">Backup Log</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Result</th>
                            </tr>
                            @foreach ($logs as $item)
                                <tr>
                                    <td>{{$item->id}}</td>
                                    <td><code>{{ucfirst($item->type)}}</code></td>
                                    <td>
                                        @if ($item->result == 1)
                                            <span class="label label-success">Success</span>
                                        @else
                                            <span class="label label-danger">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script>
        $('[data-action="create"]').click(function (event) {
            event.preventDefault();
            swal({
                type: 'warning',
                title: 'Create Backup',
                text: 'This will immediately stop your server and back up all of its contents.',
                showCancelButton: true,
                showConfirmButton: true,
                closeOnConfirm: false,
            }, function () {
                swal({
                    type: 'input',
                    title: 'Backup Name',
                    text: 'Set the name for your backup',
                    showCancelButton: true,
                    showConfirmButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true
                }, function (name) {
                    if (name === false) return false;

                    if (name.trim() === '' || name.length < 1) {
                        swal.showInputError('Invalid name!');
                        return false;
                    }

                    if (name.length > 20) {
                        swal.showInputError('Too long name (max 20)');
                        return false;
                    }

                    $.ajax({
                        method: 'POST',
                        url: '/server/{{$server->uuidShort}}/backup/create',
                        headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                        data: {name: name}
                    }).done(function (data) {
                        if (data.success === true) {
                            swal({
                                type: 'success',
                                title: 'Success!',
                                text: 'Backup is now being created, you will be notified when it finishes.'
                            }, () => {
                                location.reload();
                            });
                        } else {
                            swal({
                                type: 'error',
                                title: 'Ooops!',
                                text: (typeof data.error !== 'undefined') ? data.error : 'Failed to create backup! Please try again later...'
                            });
                        }
                    }).fail(function (jqXHR) {
                        swal({
                            type: 'error',
                            title: 'Ooops!',
                            text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                        });
                    });
                });
            });
        });

        $('[data-action="restore"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                type: 'info',
                title: 'Deploy Backup',
                text: 'This will immediately stop your server and reinstall the whole server overwriting default data with the backup.',
                showCancelButton: true,
                showConfirmButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'POST',
                    url: '/server/{{$server->uuidShort}}/backup/restore',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {id: self.data('id')}
                }).done(function (data) {
                    if (data.success === true) {
                        swal({
                            type: 'success',
                            title: 'Success!',
                            text: 'Backup is now being deployed. You will be notified when it finished.'
                        }, () => {
                            location.reload();
                        });
                    } else {
                        swal({
                            type: 'error',
                            title: 'Ooops!',
                            text: (typeof data.error !== 'undefined') ? data.error : 'Failed to restore backup! Please try again later...'
                        });
                    }
                }).fail(function (jqXHR) {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                    });
                });

            });
        });

        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this backup?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/server/{{$server->uuidShort}}/backup/delete',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done(function (data) {
                    if (data.success === true) {
                        swal({
                            type: 'success',
                            title: 'Success!',
                            text: 'You have successfully deleted this backup!'
                        });

                        self.parent().parent().parent().parent().slideUp(1000);
                        setTimeout(function () {
                            self.parent().parent().parent().parent().remove();

                            if ($.trim($('#backups').html()) == '') {
                                $('#backups').html('<div class="col-xs-12">\n' +
                                    '                <div class="alert alert-info alert-dismissable" role="alert">\n' +
                                    '                    You haven\'t any backup!\n' +
                                    '                </div>\n' +
                                    '            </div>');
                            }
                        }, 1000);
                    } else {
                        swal({
                            type: 'error',
                            title: 'Ooops!',
                            text: (typeof data.error !== 'undefined') ? data.error : 'Failed to delete backup! Please try again later...'
                        });
                    }
                }).fail(function (jqXHR) {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                    });
                });
            });
        });
    </script>
@endsection
