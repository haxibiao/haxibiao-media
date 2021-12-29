@extends('layouts.movie')

@section('title'){{ cms_seo_title() }} @stop

@section('keywords') {{ cms_seo_keywords() }} @stop

@section('description') {{ cms_seo_description() }} @stop

@section('content')
<div class="movie_category">
    <div class="container-xl padding-0">
        <div class="category-nav-panel">
            <div class="panel-box">
                <div class="panel_head clearfix">
                    <h3 class="title">
                        任务
                    </h3>
                </div>
            </div>
        </div>
        <div class="main">
            <div class="movie-list clearfix">
                <ul class="row">
                    @foreach ($tasks as $task)
                    <li class="col-lg-2 col-md-3 col-sm-3 col-xs-4 padding-10">
                        {{$task->name}}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div>

        </div>
    </div>
</div>
@endsection