@extends('layouts.layout')

@section('title')
    {{__('Process Categories')}}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_processes')])
@endsection

@section('content')
    @include('shared.breadcrumbs', ['routes' => [
        __('Processes') => route('processes.index'),
        __('Categories') => null,
    ]])
    <div class="container page-content" id="process-categories-listing">
        <div class="row">
            <div class="col">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">
                        <i class="fas fa-search"></i>
                        </span>
                    </div>
                    <input v-model="filter" class="form-control" placeholder="{{__('Search')}}...">
                </div>
            </div>
            <div class="col-8" align="right">
                @can('create-categories')
                    <button type="button" class="btn btn-secondary" data-toggle="modal"
                            data-target="#createProcessCategory">
                        <i class="fas fa-plus"></i> {{__('Category')}}
                    </button>
                @endcan
            </div>
        </div>
        <categories-listing ref="list" @edit="editCategory" @delete="deleteCategory"
                            :filter="filter" :permission="{{ \Auth::user()->hasPermissionsFor('categories') }}"></categories-listing>
    </div>

    @can('create-categories')
        <div class="modal" tabindex="-1" role="dialog" id="createProcessCategory">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('Create New Process Category')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!!Form::label('name', __('Category Name'))!!}
                            {!!Form::text('name', null, ['class'=> 'form-control', 'v-model'=> 'name',
                            'v-bind:class' => '{\'form-control\':true, \'is-invalid\':errors.name}'])!!}
                            <small class="form-text text-muted">{{ __('Category Name must be distinct') }}</small>
                            <div class="invalid-feedback" v-for="name in errors.name">@{{name}}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-success"
                                data-dismiss="modal">{{__('Close')}}</button>
                        <button type="button" class="btn btn-success ml-2" @click="onSubmit"
                                id="disabledForNow">{{__('Save')}}</button>
                    </div>
                </div>

            </div>
        </div>
    @endcan
@endsection

@section('js')
    <script src="{{mix('js/processes/categories/index.js')}}"></script>
    
    @can('create-categories')
        <script>
            new Vue({
                el: '#createProcessCategory',
                data: {
                    errors: {},
                    name: '',
                    status: 'ACTIVE',
                },
                methods: {
                    onSubmit() {
                        this.errors = {};
                        let that = this;
                        ProcessMaker.apiClient.post('process_categories', {
                            name: this.name,
                            status: this.status
                        })
                            .then(response => {
                                ProcessMaker.alert('{{__('Category successfully added ')}}', 'success');
                                window.location = '/processes/categories/' + response.data.id + '/edit';
                            })
                            .catch(error => {
                                if (error.response.status === 422) {
                                    that.errors = error.response.data.errors
                                }
                            });
                    }
                }
            })
        </script>
    @endcan
@endsection
