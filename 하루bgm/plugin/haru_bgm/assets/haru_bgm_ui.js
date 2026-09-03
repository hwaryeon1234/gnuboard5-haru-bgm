(function(){
    'use strict';

    function addClass(el, name){ if(el && !el.classList.contains(name)) el.classList.add(name); }
    function removeClass(el, name){ if(el) el.classList.remove(name); }

    function ensureViewport(){
        var meta = document.querySelector('meta[name="viewport"]');
        if(!meta){
            meta = document.createElement('meta');
            meta.name = 'viewport';
            meta.setAttribute('content', 'width=device-width, initial-scale=1, viewport-fit=cover');
            (document.head || document.documentElement).appendChild(meta);
            return;
        }
        var content = String(meta.getAttribute('content') || '').trim();
        if(!/viewport-fit\s*=\s*/i.test(content)) meta.setAttribute('content', (content ? content + ', ' : '') + 'viewport-fit=cover');
    }

    function labelResponsiveTables(root){
        if(!root) return;
        var tables = root.querySelectorAll('.hb-table');
        for(var t=0;t<tables.length;t++){
            var table = tables[t];
            var heads = table.querySelectorAll('thead th');
            var labels = [];
            for(var h=0;h<heads.length;h++) labels.push((heads[h].textContent || '').trim());
            var rows = table.querySelectorAll('tbody tr');
            for(var r=0;r<rows.length;r++){
                var cells = rows[r].children;
                for(var c=0;c<cells.length;c++){
                    var cell = cells[c];
                    if(cell.tagName !== 'TD') continue;
                    var colspan = parseInt(cell.getAttribute('colspan') || '1', 10);
                    if(colspan > 1){
                        cell.setAttribute('data-hb-full','1');
                        continue;
                    }
                    if(!cell.hasAttribute('data-hb-label')) cell.setAttribute('data-hb-label', labels[c] || '');
                }
            }
            if(labels.length) table.classList.add('hb-table-labels-ready');
        }
    }

    function parseHmsSeconds(text){
        var m = String(text || '').match(/(\d{2}):(\d{2}):(\d{2})/);
        if(!m) return null;
        return parseInt(m[1],10)*3600 + parseInt(m[2],10)*60 + parseInt(m[3],10);
    }

    function hmSeconds(text){
        var p = String(text || '00:00').split(':');
        return (parseInt(p[0] || '0',10)*3600) + (parseInt(p[1] || '0',10)*60);
    }

    function adminServerDate(data){
        var text = String((data && data.server_time) || (data && data.server_date) || '');
        var m = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if(!m) return new Date();
        return new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10));
    }

    function adminAddDays(date, amount){
        var out = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        out.setDate(out.getDate() + amount);
        return out;
    }

    function adminYmd(date){
        function p(n){ return String(n).padStart(2,'0'); }
        return date.getFullYear()+'-'+p(date.getMonth()+1)+'-'+p(date.getDate());
    }

    function adminDayAllowed(item, date){
        var days = String(item && item.days != null ? item.days : '0,1,2,3,4,5,6').split(',');
        return days.indexOf(String(date.getDay())) !== -1;
    }

    function adminDateAllowed(item, date){
        var ymd = adminYmd(date);
        var start = String((item && item.start_date) || '');
        var end = String((item && item.end_date) || '');
        if(start && start !== '0000-00-00' && ymd < start) return false;
        if(end && end !== '0000-00-00' && ymd > end) return false;
        return true;
    }

    function adminBlockLive(item, nowSeconds, serviceDate){
        var st=hmSeconds(item.start), en=hmSeconds(item.end);
        if(st === en) return false;
        var live = en > st ? (nowSeconds >= st && nowSeconds < en) : (nowSeconds >= st || nowSeconds < en);
        if(!live) return false;
        var d = serviceDate;
        if(en < st && nowSeconds < en) d = adminAddDays(serviceDate, -1);
        return adminDayAllowed(item, d) && adminDateAllowed(item, d);
    }

    function setupAdminOnAir(app){
        var box = app && app.querySelector('#hbAdminOnAir');
        var text = app && app.querySelector('#hbAdminOnAirText');
        if(!box || !text) return;
        var api = box.getAttribute('data-api') || '';
        var base = (box.getAttribute('data-base') || '').replace(/\/$/, '');
        if(!api) return;
        var timer = null;

        function setWaiting(label){
            box.classList.remove('is-live');
            text.textContent = label || '방송 대기 중';
            box.href = base + '/admin/operation.php';
            box.title = '공용 운영판 열기';
        }

        function chooseActive(data){
            var broadcast = (data && data.broadcast) || null;
            if(broadcast && broadcast.mode === 'stop') return {type:'broadcast_stop', data:{title:'사이트 전체 정지', revision:broadcast.revision || 0}};
            if(broadcast && broadcast.mode === 'manual' && broadcast.item) {
                var bi = Object.assign({}, broadcast.item);
                bi.revision = broadcast.revision || 0;
                return {type:'broadcast', data:bi, priority:-1000, tie:0, sort:0};
            }
            var now = parseHmsSeconds(data && data.server_time);
            var serviceDate = adminServerDate(data);
            if(now === null){ var d = new Date(); now=d.getHours()*3600+d.getMinutes()*60+d.getSeconds(); serviceDate=new Date(d.getFullYear(),d.getMonth(),d.getDate()); }
            var settings = (data && data.settings) || {};
            var win = Math.max(30, Math.min(600, parseInt(settings.single_window_seconds || 90,10)));
            var candidates = [];
            (Array.isArray(data && data.blocks) ? data.blocks : []).forEach(function(b){
                if(adminBlockLive(b, now, serviceDate)) candidates.push({type:'block', data:b, priority:parseInt(b.priority || 999,10), tie:(hmSeconds(b.start)-now+86400)%86400, sort:parseInt(b.sort || 0,10) || 0});
            });
            (Array.isArray(data && data.items) ? data.items : []).forEach(function(it){
                var occurrenceDate = serviceDate;
                var occurrenceText = String(it.service_date || '');
                if(occurrenceText){
                    var om = occurrenceText.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if(om) occurrenceDate = new Date(parseInt(om[1],10), parseInt(om[2],10)-1, parseInt(om[3],10));
                }
                if(!adminDayAllowed(it, occurrenceDate) || !adminDateAllowed(it, occurrenceDate)) return;
                var target=hmSeconds(it.time), diff=now-target;
                if(adminYmd(occurrenceDate) !== adminYmd(serviceDate)) diff = now + 86400 - target;
                if(diff >= 0 && diff <= win) candidates.push({type:'single', data:it, priority:parseInt(it.priority || 999,10), tie:diff, sort:parseInt(it.sort || 0,10) || 0});
            });
            candidates.sort(function(a,b){
                if(a.priority !== b.priority) return a.priority-b.priority;
                if(a.tie !== b.tie) return a.tie-b.tie;
                if(a.sort !== b.sort) return a.sort-b.sort;
                var ar=String((a.data && (a.data.log_id != null ? a.data.log_id : a.data.id)) || '0').replace(/\D+/g,''),
                    br=String((b.data && (b.data.log_id != null ? b.data.log_id : b.data.id)) || '0').replace(/\D+/g,'');
                var ai=parseInt(ar || '0',10), bi=parseInt(br || '0',10);
                return ai-bi;
            });
            return candidates.length ? candidates[0] : null;
        }

        function refresh(){
            fetch(api + (api.indexOf('?') >= 0 ? '&' : '?') + '_=' + Date.now(), {credentials:'same-origin', cache:'no-store'})
                .then(function(r){ if(!r.ok) throw new Error('http_'+r.status); return r.json(); })
                .then(function(data){
                    if(!data || !data.ok){ setWaiting('방송 상태 확인'); return; }
                    var active = chooseActive(data);
                    if(!active){ setWaiting('현재 방송 대기'); return; }
                    var item = active.data || {};
                    var href = base + '/admin/operation.php';
                    if(active.type === 'broadcast_stop') {
                        box.href = href; box.classList.remove('is-live'); text.textContent = '사이트 전체 정지'; box.title = '공용 운영판 열기'; return;
                    }
                    if(active.type === 'single') href = base + '/admin/schedule_form.php?sc_id=' + encodeURIComponent(item.id || 0);
                    else if(active.type === 'broadcast') href = base + '/admin/operation.php';
                    else if(item.kind === 'range') href = base + '/admin/schedule_form.php?sc_id=' + encodeURIComponent(item.log_id || String(item.id || '').replace(/^range_/,''));
                    else href = base + '/admin/block_form.php?bl_id=' + encodeURIComponent(item.id || 0);
                    box.href = href;
                    box.classList.add('is-live');
                    text.textContent = active.type === 'broadcast' ? ('전체송출 · ' + (item.music_title || item.title || '현재 방송 중')) : (item.title || '현재 방송 중');
                    box.title = active.type === 'broadcast' ? ('수동 전체송출 · revision ' + (item.revision || 0)) : '현재 방송 편성으로 이동';
                })
                .catch(function(){ setWaiting('방송 상태 확인'); });
        }
        refresh();
        timer = window.setInterval(refresh, 15000);
        document.addEventListener('visibilitychange', function(){ if(!document.hidden) refresh(); });
    }

    function setupAdminNav(app){
        if(!app) return;
        var toggle = app.querySelector('.hb-side-toggle');
        var backdrop = app.querySelector('.hb-side-backdrop');
        var nav = app.querySelector('.hb-side-nav');
        if(!toggle || !nav) return;

        function close(){
            removeClass(app, 'hb-nav-open');
            toggle.setAttribute('aria-expanded','false');
        }
        function open(){
            addClass(app, 'hb-nav-open');
            toggle.setAttribute('aria-expanded','true');
        }
        toggle.addEventListener('click', function(e){
            e.preventDefault();
            if(app.classList.contains('hb-nav-open')) close(); else open();
        });
        if(backdrop) backdrop.addEventListener('click', close);
        nav.addEventListener('click', function(e){ if(e.target.closest && e.target.closest('a')) close(); });
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape') close(); });
        window.addEventListener('resize', function(){
            if(window.innerWidth > 820) close();
        });
    }

    function setupTrackLists(root){
        if(!root) return;
        function rowsOf(list){
            var rows = [];
            if(!list) return rows;
            var children = list.children || [];
            for(var i=0;i<children.length;i++){
                if(children[i].classList && children[i].classList.contains('hb-track-row')) rows.push(children[i]);
            }
            return rows;
        }
        function syncList(list){
            if(!list) return;
            var rows = rowsOf(list);
            for(var i=0;i<rows.length;i++){
                var no = rows[i].querySelector('.hb-track-no');
                if(no) no.textContent = i + 1;
                var up = rows[i].querySelector('.hb-track-up');
                var down = rows[i].querySelector('.hb-track-down');
                if(up) up.disabled = (i === 0);
                if(down) down.disabled = (i === rows.length - 1);
            }
            var add = list.querySelector('.hb-track-add');
            var count = list.querySelector('.hb-track-count');
            if(count) count.textContent = rows.length;
            if(add){
                var max = Math.max(1, parseInt(add.getAttribute('data-hb-track-max') || '100',10));
                var locked = rows.length >= max;
                add.disabled = locked;
                add.setAttribute('aria-disabled', locked ? 'true' : 'false');
            }
        }
        function resetRow(row){
            if(!row) return;
            var controls = row.querySelectorAll('select,input,textarea');
            for(var i=0;i<controls.length;i++){
                var el = controls[i];
                if(el.tagName === 'SELECT') el.selectedIndex = 0;
                else if(el.type === 'checkbox' || el.type === 'radio') el.checked = false;
                else if(el.type !== 'button' && el.type !== 'submit') el.value = '';
            }
            row.classList.remove('is-error','is-active','hb-manual-current');
        }
        root.addEventListener('click', function(e){
            var target = e.target.closest && e.target.closest('.hb-track-add,.hb-track-up,.hb-track-down,.hb-track-clear');
            if(!target || !root.contains(target)) return;
            var list = target.closest('.hb-track-list');
            if(!list) return;
            e.preventDefault();

            if(target.classList.contains('hb-track-add')){
                var rows = rowsOf(list);
                var max = Math.max(1, parseInt(target.getAttribute('data-hb-track-max') || '100',10));
                if(rows.length >= max){ syncList(list); return; }
                var source = rows.length ? rows[rows.length - 1] : null;
                if(!source) return;
                var row = source.cloneNode(true);
                resetRow(row);
                var wrap = list.querySelector('.hb-track-add-wrap');
                list.insertBefore(row, wrap || null);
                syncList(list);
                var focus = row.querySelector('select,input,textarea');
                if(focus) focus.focus();
                return;
            }

            var row = target.closest('.hb-track-row');
            if(!row) return;
            var rowsNow = rowsOf(list);
            var idx = rowsNow.indexOf(row);
            if(idx < 0) return;
            if(target.classList.contains('hb-track-clear')){
                resetRow(row);
            }else if(target.classList.contains('hb-track-up')){
                if(idx > 0) list.insertBefore(row, rowsNow[idx - 1]);
            }else if(target.classList.contains('hb-track-down')){
                if(idx < rowsNow.length - 1) list.insertBefore(rowsNow[idx + 1], row);
            }
            syncList(list);
        });
        var lists = root.querySelectorAll('.hb-track-list');
        for(var i=0;i<lists.length;i++) syncList(lists[i]);
    }

    function setupAdminTokenBypass(root){
        // 이 서버(그누보드 커스텀 관리자 테마)는 admin.js가 문서 전체의 폼 제출 버튼 클릭에
        // 전역으로 걸려 있어, 클릭 시마다 /adm/ajax.token.php로 토큰을 새로 요청합니다.
        // 그 엔드포인트는 admin_referer_check()로 Referer 경로에 "/adm/"이 포함된 요청만
        // 통과시키는데, 하루BGM 관리자 화면은 /plugin/haru_bgm/admin/ 경로에 있어 이 조건을
        // 만족하지 못해 매번 "토큰 정보가 올바르지 않습니다"로 막힙니다.
        // 하루BGM 폼은 이미 서버가 GET 렌더링 시점에 정상 발급한 표준 token 값을
        // hb_csrf_field()로 심어 두고 있으므로(다른 커스텀 관리자 화면과 동일한 방식),
        // admin.js가 그 값을 굳이 재발급받으려는 시도만 하루BGM 폼에서 가로채 건너뛰고
        // 이미 있는 값 그대로 정상 제출합니다. 서버 측 check_admin_token() 검증은
        // 그대로 받으므로 이 서버의 관리자 토큰 규약 자체는 동일하게 지켜집니다.
        if(!root || root.getAttribute('data-hb-token-bypass-bound') === '1') return;
        root.setAttribute('data-hb-token-bypass-bound', '1');
        root.addEventListener('click', function(e){
            var btn = e.target.closest ? e.target.closest('button[type="submit"], input[type="submit"]') : null;
            if(!btn) return;
            var form = btn.form;
            if(!form || !root.contains(form)) return;
            // admin.js의 버블링 단계 document 클릭 핸들러(get_ajax_token 재발급)가
            // 실행되지 않도록, 캡처 단계에서 이벤트 전파 자체를 끊습니다.
            e.stopImmediatePropagation();
        }, true);
    }

    function mountAdminApp(){
        var app = document.getElementById('haruBgmAdminApp');
        if(!app) return;
        // G5 관리자 셸 안에서 표 라벨·내비게이션·상태 표시만 초기화합니다.
        labelResponsiveTables(app);
        setupAdminNav(app);
        setupAdminOnAir(app);
        setupTrackLists(app);
        setupAdminTokenBypass(app);
    }

    function init(){
        ensureViewport();
        mountAdminApp();
    }

    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once:true});
    else init();
})();
