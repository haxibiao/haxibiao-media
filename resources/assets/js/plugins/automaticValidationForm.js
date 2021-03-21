(function automaticValidationForm() {
    function validationValue({ rule, target, value }) {
        const rules = {
            phoneNumber: {
                validate: checkPhoneNumber,
                message: '手机号码格式不正确',
            },
            password: {
                validate: checkPassword,
                message: '至少6个字符，包含字母和数字',
            },
            nickname: {
                validate: checkNickname,
                message: '昵称长度至少为2',
            },
        };
        if (!rules[rule] || (rules[rule] && rules[rule].validate(value))) {
            $(target)
                .find('.help-block')
                .children()
                .text('格式符合要求');
            $(target)
                .removeClass('with-error')
                .addClass('with-success');
            return true;
        } else {
            $(target)
                .find('.help-block')
                .children()
                .text(rules[rule].message);
            $(target)
                .removeClass('with-success')
                .addClass('with-error');
            return false;
        }

        function checkPhoneNumber(number) {
            return /^1[3|4|5|7|8]\d{9}$/.test(number);
        }

        function checkPassword(password) {
            return /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/.test(password);
        }

        function checkNickname(name) {
            return name && name.length > 1 && name.length < 12;
        }
    }

    $('[ref="formValidate"] [validate-rule] > input').on('blur', function onBlur(event) {
        const value = $(this).val();
        const target = $(this).parent();
        const rule = target.attr('validate-rule');
        const required = target.attr('data-required');
        if (required || value.length > 0) {
            validationValue({ rule, value, target });
        } else {
            $(target)
                .removeClass('with-success')
                .removeClass('with-error');
        }
    });

    $('[ref="formValidate"] [validate-rule] > input').on('input propertychange', function onChange(event) {
        const value = $(this).val();
        const target = $(this).parent();
        const rule = target.attr('validate-rule');
        const required = target.attr('data-required');
        if (value.length > 0 && ($(target).hasClass('with-success') || $(target).hasClass('with-error'))) {
            validationValue({ rule, value, target });
        }
    });

    $('[form-validate]').on('click', function onClickValidateButton(event) {
        const form = $(this).attr('form-validate');
        let count = 0;
        let pass = 0;
        $(form)
            .find('[validate-rule]')
            .each(function onValidateInput(index) {
                const required = $(this).attr('data-required');
                const rule = $(this).attr('validate-rule');
                const value = $(this)
                    .children('input')
                    .val();
                if (required || value.length > 0) {
                    count++;
                    if (validationValue({ rule, value, target: this })) {
                        pass++;
                    }
                } else {
                    $(this)
                        .removeClass('with-success')
                        .removeClass('with-error');
                }
            });
        if (count == pass) {
            $(this).trigger('login');
        }
    });
})();
