function encodeStr(array) {
    let keyVal = array.map(i => btoa(encodeURIComponent(i))).join('');
    const replacements = ['Y', 'M', '=', 'A', 'N'];

    replacements.forEach(i => {
        keyVal = keyVal.replaceAll(i, '/' + btoa(i + '_apee'));
    });

    return btoa(encodeURIComponent(keyVal));
}

function validateInput(url, password, desc, guoqi, end) {
    try {
        new URL(url);
    } catch {
        return ['input-url', 'Invalid URL'];
    }

    if (!/^\w{0,20}$/.test(password)) {
        return ['input-password', 'Password must be 0-20 alphanumeric characters'];
    }

    if (desc.length > 200) {
        return ['input-desc', 'Description cannot exceed 200 characters'];
    }

    if (guoqi < 0 || guoqi > 365) {
        return ['input-day', 'Days must be between 0 and 365'];
    }

    if (end && !/^\w{6,20}$/.test(end)) {
        return ['input-end', 'Invalid end parameter'];
    }

    return null;
}

function createWork() {
    const url = $('#input-url').val();
    const password = $('#input-password').val();
    const desc = $('#input-desc').val();
    const guoqi = Number($('#input-day').val()) || 0;
    const end = $('#input-end').val();

    const validationError = validateInput(url, password, desc, guoqi, end);
    if (validationError) {
        const [field, message] = validationError;
        $(`#${field}`).addClass('is-invalid').focus();
        alert(message);
        return;
    }

    $('.page-home .btns .createWork').attr('disabled', true).text('正在生成中...');

    const d = Date.now();
    const keyVal = encodeStr([url, password, guoqi, d]);

    $.post('api/add_url.php', { a: url, b: password, c: desc, d: guoqi, e: d, f: keyVal, g: end }, function (data) {
        $('.page-home .btns .createWork').removeAttr('disabled').text('生成链接');

        if (data.code === 200) {
            clearForm();
            Poncon.load.result = true;
            location.hash = '/result';
            $('.page-result .line-list').html(makeHtml(data));
        } else {
            alert(data.msg);
        }
    });
}

function makeHtml(data) {
    const keyNames = ['短链接', '分享链接', '原链接', '密码', '有效期', '描述'];
    const values = [
        `<a class="short-url" href="${location.origin}/${data.data.end}${data.data.password ? `/${data.data.password}` : ''}" target="_blank">${location.origin}/${data.data.end}${data.data.password ? `/${data.data.password}` : ''}</a>`,
        `<a href="/s/${data.data.end}" target="_blank" class="text-danger">${location.origin}/s/${data.data.end}</a>`,
        data.data.url,
        data.data.password || '-',
        data.data.guoqi === 0 ? '永久有效' : `剩余 ${data.data.guoqi} 天`,
        $('<div>').text(data.data.desc).html()
    ];

    return keyNames.map((name, i) => values[i] ? `<div class="mb-2 text-truncate"><b>${name}：</b>${values[i]}</div>` : '').join('');
}

function clearForm() {
    $('.page-home input').val('').removeClass('is-invalid');
}

function share_submitPassword() {
    const password = $('.page-share .input-password').val();
    const end = location.hash.split('/')[2];
    history.replaceState({}, null, `#/share/${end}/${password}`);
    share_load(end, password, 'click');
}

$(document).ready(function () {
    new ClipboardJS('.copybtn');
    router(location.hash);

    $('input').on('keyup', function () {
        $(this).removeClass('is-invalid');
        $(this).parent().removeClass('is-invalid');
    });

    window.addEventListener('hashchange', (event) => router(new URL(event.newURL).hash));
});
