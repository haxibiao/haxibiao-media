@extends('layouts.movie')

@section('title'){{ cms_seo_title() }} @stop

@section('keywords') {{ cms_seo_keywords() }} @stop

@section('description') {{ cms_seo_description() }} @stop

@section('content')
<div class="movie_task" >
    <div class="container-xl padding-0">
        <div class="main">
            <div class="row task_header">
               <div class="gold col-lg-2 col-md-3 col-sm-3 col-xs-4 ">
                    <span>当前金币</span>
                    <p>1000</p>
                </div>
                <div class="gold col-lg-2 col-md-3 col-sm-3 col-xs-4">
                    <span>今日领取</span>
                    <p>80</p>
                </div>
                <button class="gold_detail col-lg-2 col-md-3 col-sm-3 col-xs-4">
                    <span>金币明细</span>
                </button>
            </div>
            <div class="row clearfix ">
                <div class="task_left col-12 col-lg-8">
                    <div class="task_back task_main">
                        <p class="task_title">
                            每日任务
                        </p>
                        <ul class="task_scroll">
                            @foreach ($tasks as $task)
                            <li class="task_item">
                                <div class="item_left">
                                    <img class="item_icon" lazy="loaded" src='/images/task_1.png' />
                                    <div>
                                        <div class="item_name">
                                            <p>{{$task->name}}</p>
                                            <div class="gold_info">
                                                <image class="gold_icon" lazy="loaded" src='/images/task_golb.png' />
                                                <span>
                                                    +100金币
                                                </span>
                                            </div>
                                        </div>
                                        <span>多喝水发射基地粉红色的讲课费艰苦奋斗</span>
                                    </div>
                                </div>
                                <button class="item_button">
                                    <a>去分享</a>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="task_right col-12 col-lg-4">
                    <div class="task_back sign_in">
                        <div class="time">
                            <p>·&nbsp;&nbsp;2021&nbsp;&nbsp;·</p>
                            <span>7</span>
                            <div class="time_day">
                                <p>12月&nbsp;&nbsp;&nbsp;星期二</p>
                            </div>
                        </div>
                        <div class="describe row no-gutters">
                            <p>每日签到</p>
                            <span>完成签到获得元宝奖励</span>
                        </div>
                        <ul class="row no-gutters">
                            @foreach ([1,2,3,4,5,6] as $task)
                            <li class="col-3">
                                <div class="sign_item">
                                    <p>第{{$task}}天</p>
                                    <image class="sign_icon" src='/images/task_golb.png' />
                                    <span>+2</span>
                                </div>
                            </li>
                            @endforeach
                            <li class="col-6">
                                <div class="sign_item">
                                    <p>第7天</p>
                                    <img class="sign_icon" lazy="loaded" src='/images/task_sign.png' />
                                    <span>+2</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="task_back">
                <p class="task_title">金币兑换</p>
                <ul class="row">
                    @foreach ([1,2,3] as $task)
                    <li class="store_item">
                        <image class="store_icon" src='/images/store_2.png' />
                        <p>一天会员</p>
                        <span>100金币</span>
                        <button class="store_button">
                            <a>兑换</a>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="task_back">
                <p class="task_title">会员专享权益</p>
                <ul class="row justify-content-xs-center">
                    @foreach ([1,2,3] as $task)
                    <li class="menber_item">
                        <div class="item_left">
                            <p>广告特权</p>
                            <div style="background:#C8A06A;width:30px;height:2px"></div>
                            <span>VIP尊享广告特权</span>
                        </div>
                        <div class="item_right">
                            <image class="menber_icon" src='https://cos.haxibiao.com/app/juhaokan/avatar/avatar_1.jpeg' />
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection