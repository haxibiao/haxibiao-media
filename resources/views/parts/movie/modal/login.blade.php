<div class="modal fade" id="login-modal" tabindex="-1" role="dialog" aria-labelledby="hxfLoginModa" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="login-panel">
                <div class="nav-tab">
                    <div class="title active" data-panel="#login-form">登录</div>
                    <div class="title" data-panel="#register-form">注册</div>
                </div>
                <form id="login-form" class="panel active" ref="formValidate">
                    <div class="app-input-group" validate-rule="phoneNumber" validate-trigger="blur"
                        data-required="true">
                        <input type="tel" name="phone" maxlength="11" id="login-account" class="form-control"
                            placeholder="请输入手机号" />
                        <div class="help-block">
                            <p class="help-text"></p>
                        </div>
                    </div>
                    <div class="app-input-group" validate-rule="password" validate-trigger="blur" data-required="true">
                        <input type="password" name="pwd" maxlength="16" id="login-password" class="form-control"
                            placeholder="请输入密码" />
                        <div class="help-block">
                            <p class="help-text"></p>
                        </div>
                    </div>
                    <p class="help-message">
                        <a href="javascript:void(0)" id="forget-password" target="_blank">忘记密码？</a>
                    </p>
                    <div id="login-submit" class="app-btn" form-validate="#login-form" validate-trigger="click">
                        <span class="text">登录</span>
                    </div>
                    <p class="explain">
                        登录即代表你同意
                        <a target="_blank" href="/">内涵电影用户协议</a>和
                        <a target="_blank" href="/">内涵电影隐私政策</a>
                    </p>
                </form>
                <form id="register-form" class="panel" ref="formValidate">
                    <div class="app-input-group" validate-rule="phoneNumber" validate-trigger="blur"
                        data-required="true">
                        <input type="tel" name="phone" maxlength="11" id="register-account" class="form-control"
                            placeholder="常用手机号" />
                        <div class="help-block">
                            <p class="help-text"></p>
                        </div>
                    </div>
                    <div class="app-input-group" validate-rule="password" validate-trigger="blur" data-required="true">
                        <input type="password" name="pwd" maxlength="16" id="register-password" class="form-control"
                            placeholder="设置密码" />
                        <div class="help-block">
                            <p class="help-text"></p>
                        </div>
                    </div>
                    <div class="app-input-group" validate-rule="name" validate-trigger="blur">
                        <input type="text" name="name" maxlength="12" id="register-name" class="form-control"
                            placeholder="设置昵称" />
                        <div class="help-block">
                            <p class="help-text"></p>
                        </div>
                    </div>
                    <p class="help-message">
                    </p>
                    <div id="register-submit" class="app-btn" form-validate="#register-form" validate-trigger="click">
                        <span class="text">注册</span>
                    </div>
                    <p class="agreement">
                        登录即代表你同意
                        <a target="_blank" href="/">内涵电影用户协议</a>和
                        <a target="_blank" href="/">内涵电影隐私政策</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
