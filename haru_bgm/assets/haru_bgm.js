(function(){
    const cfg = window.HARU_BGM || {};
    const storagePrefix = cfg.storagePrefix || 'haru_bgm_';
    function storageKey(name){ return storagePrefix + name; }
    const audio = document.getElementById('hbAudio');
    const ytWrap = document.getElementById('hbYoutubeWrap');
    const ytNode = document.getElementById('hbYouTubePlayer');
    const enableBtn = document.getElementById('hbEnableSound');
    const todayOffBtn = document.getElementById('hbTodayOff');
    const stopBtn = document.getElementById('hbStopSound');
    const clockEl = document.getElementById('hbClock');
    const countdownEl = document.getElementById('hbCountdown');
    const nowTitle = document.getElementById('hbNowTitle');
    const nowDesc = document.getElementById('hbNowDesc');
    const soundState = document.getElementById('hbSoundState');
    const volume = document.getElementById('hbVolume');
    const volumeText = document.getElementById('hbVolumeText');
    const todayList = document.getElementById('hbTodayList');
    const statusText = document.getElementById('hbStatusText');
    const policyText = document.getElementById('hbPolicyText');

    let settings = {
        priority_mode: 'user_first',
        priority_label: '개인 우선',
        single_window_seconds: 90,
        fadeout_seconds: 4,
        block_end_action: 'fade_stop',
        auto_refresh_seconds: 60
    };
    let schedules = [];
    let blocks = [];
    let soundReady = localStorage.getItem(storageKey('sound_ready')) === '1';
    let audioUnlocked = false;
    let activeBlock = null;
    let activeBlockIndex = -1;
    let currentAuto = null;
    let refreshTimer = null;
    let fadeTimer = null;
    let ytPlayer = null;
    let ytReady = false;
    let ytApiPromise = null;
    let ytPlayerPromise = null;
    let ytEndingLock = false;

    function pad(n){ return String(n).padStart(2,'0'); }
    function dateKey(d){ d = d || new Date(); return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate()); }
    function hms(d){ return pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds()); }
    function secOf(hmText){ const p=String(hmText||'00:00').split(':'); return parseInt(p[0]||'0',10)*3600+parseInt(p[1]||'0',10)*60; }
    function nowSec(){ const d=new Date(); return d.getHours()*3600+d.getMinutes()*60+d.getSeconds(); }
    function fmtRemain(sec){ if(sec < 0) sec += 86400; const h=Math.floor(sec/3600); const m=Math.floor((sec%3600)/60); const s=sec%60; return (h? h+'시간 ':'')+m+'분 '+s+'초'; }
    function todayOffKey(){ return storageKey('today_off_'+dateKey()); }
    function isTodayOff(){ return localStorage.getItem(todayOffKey()) === '1'; }
    function setTodayOff(v){ if(v){ localStorage.setItem(todayOffKey(), '1'); }else{ localStorage.removeItem(todayOffKey()); } updateButtons(); }
    function playedKey(item){ return storageKey('played_'+dateKey()+'_'+(item.id||'0')+'_'+(item.time||'')); }
    function isPlayed(item){ return localStorage.getItem(playedKey(item)) === '1'; }
    function markPlayed(item){ localStorage.setItem(playedKey(item), '1'); }
    function blockDoneKey(block){ return storageKey('block_done_'+dateKey()+'_'+(block.id||'0')); }
    function isBlockDone(block){ return localStorage.getItem(blockDoneKey(block)) === '1'; }
    function markBlockDone(block){ localStorage.setItem(blockDoneKey(block), '1'); }
    function clearBlockDone(block){ localStorage.removeItem(blockDoneKey(block)); }
    function setState(text){ if(soundState) soundState.textContent = text; }
    function setStatus(text){ if(statusText) statusText.textContent = text; }
    function normalizePriority(x){ const n = parseInt(x, 10); return Number.isFinite(n) ? n : 999; }
    function getSavedVolume(){ return volume ? Math.max(0, Math.min(100, parseInt(volume.value || '80', 10))) : 80; }

    function blockContainsNow(block){
        const ns = nowSec();
        const st = secOf(block.start);
        const en = secOf(block.end);
        if(st === en) return false;
        if(en > st) return ns >= st && ns < en;
        return ns >= st || ns < en;
    }

    function secondsUntilStart(item){ return (secOf(item.time || item.start) - nowSec() + 86400) % 86400; }

    function chooseBlockIndex(block, current){
        const items = Array.isArray(block.items) ? block.items : [];
        if(!items.length) return -1;
        if(block.mode === 'random'){
            if(items.length === 1) return 0;
            let n = Math.floor(Math.random() * items.length);
            if(n === current) n = (n + 1) % items.length;
            return n;
        }
        const next = current + 1;
        if(next < items.length) return next;
        return block.repeat ? 0 : -1;
    }

    function loadYouTubeApi(){
        if(window.YT && window.YT.Player) return Promise.resolve();
        if(ytApiPromise) return ytApiPromise;
        ytApiPromise = new Promise(function(resolve){
            const old = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function(){
                if(typeof old === 'function') old();
                resolve();
            };
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const first = document.getElementsByTagName('script')[0];
            first.parentNode.insertBefore(tag, first);
        });
        return ytApiPromise;
    }

    function ensureYouTubePlayer(){
        if(!ytNode) return Promise.reject(new Error('youtube_node_missing'));
        if(ytPlayer && ytReady) return Promise.resolve(ytPlayer);
        if(ytPlayerPromise) return ytPlayerPromise;
        ytPlayerPromise = loadYouTubeApi().then(function(){
            return new Promise(function(resolve){
                ytPlayer = new YT.Player('hbYouTubePlayer', {
                    height: '360',
                    width: '100%',
                    videoId: '',
                    playerVars: { playsinline: 1, rel: 0, modestbranding: 1, controls: 1, origin: location.origin },
                    events: {
                        onReady: function(){ ytReady = true; resolve(ytPlayer); },
                        onStateChange: function(e){
                            if(e.data === YT.PlayerState.ENDED){
                                if(ytEndingLock) return;
                                ytEndingLock = true;
                                setTimeout(function(){ ytEndingLock = false; }, 500);
                                handleMediaEnded();
                            }
                        }
                    }
                });
            });
        });
        return ytPlayerPromise;
    }

    function showFilePlayer(){
        if(audio) audio.style.display = '';
        if(ytWrap) ytWrap.style.display = 'none';
    }

    function showYouTubePlayer(){
        if(audio) audio.style.display = 'none';
        if(ytWrap) ytWrap.style.display = '';
    }

    function stopYouTube(){
        try{ if(ytPlayer && ytReady) ytPlayer.stopVideo(); }catch(e){}
    }

    function pauseYouTube(){
        try{ if(ytPlayer && ytReady) ytPlayer.pauseVideo(); }catch(e){}
    }

    function isYouTubePlaying(){
        try{
            if(!ytPlayer || !ytReady || !window.YT) return false;
            const st = ytPlayer.getPlayerState();
            return st === YT.PlayerState.PLAYING || st === YT.PlayerState.BUFFERING;
        }catch(e){ return false; }
    }

    function isMediaPlaying(){
        const audioPlaying = audio && !audio.paused && !audio.ended;
        return !!audioPlaying || isYouTubePlaying();
    }

    function stopMedia(reset){
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        if(audio){ audio.pause(); if(reset){ try{ audio.currentTime = 0; }catch(e){} } }
        stopYouTube();
        currentAuto = null;
    }

    function fadeOutAndStop(seconds, message){
        seconds = Math.max(0, parseInt(seconds || 0, 10));
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        if(seconds <= 0){ stopMedia(true); if(message) setStatus(message); return; }
        const steps = Math.max(1, seconds * 5);
        let left = steps;
        const audioStart = audio ? audio.volume : 0;
        let ytStart = getSavedVolume();
        try{ if(ytPlayer && ytReady) ytStart = ytPlayer.getVolume(); }catch(e){}
        fadeTimer = setInterval(function(){
            left--;
            const ratio = Math.max(0, left / steps);
            if(audio && !audio.paused) audio.volume = audioStart * ratio;
            try{ if(ytPlayer && ytReady && isYouTubePlaying()) ytPlayer.setVolume(Math.round(ytStart * ratio)); }catch(e){}
            if(left <= 0){
                clearInterval(fadeTimer);
                fadeTimer = null;
                stopMedia(true);
                applySavedVolume();
                if(message) setStatus(message);
            }
        }, 200);
    }

    function stopActiveBlock(message){
        activeBlock = null;
        activeBlockIndex = -1;
        currentAuto = null;
        if(message) setStatus(message);
    }

    function applySavedVolume(){
        if(!volume) return;
        const saved = localStorage.getItem(storageKey('volume'));
        if(saved !== null && !Number.isNaN(parseInt(saved,10))) volume.value = Math.max(0, Math.min(100, parseInt(saved,10)));
        if(volumeText) volumeText.textContent = volume.value + '%';
        const vol = parseInt(volume.value,10);
        if(audio) audio.volume = vol/100;
        try{ if(ytPlayer && ytReady) ytPlayer.setVolume(vol); }catch(e){}
    }

    function updateButtons(){
        if(enableBtn){
            enableBtn.textContent = soundReady ? '✅ 음악 알림 켜짐' : '🔊 음악 알림 켜기';
            enableBtn.classList.toggle('hb-btn-primary', !soundReady);
        }
        if(todayOffBtn){
            todayOffBtn.textContent = isTodayOff() ? '🌙 오늘 꺼짐 해제' : '🌙 오늘만 끄기';
            todayOffBtn.classList.toggle('hb-btn-soft-on', isTodayOff());
        }
        if(policyText){
            policyText.textContent = (cfg.mode === 'admin_operation' ? '관리자 공용 운영판 · ' : '') + '우선순위: ' + (settings.priority_label || '개인 우선') + ' · 정각 허용범위 ' + (settings.single_window_seconds || 90) + '초';
        }
        if(isTodayOff()){
            setState('오늘 꺼짐');
            setStatus('오늘은 자동재생을 쉬는 중입니다. 해제하면 다시 시간표대로 재생됩니다.');
        }else if(soundReady){
            setState('켜짐');
            setStatus(cfg.mode === 'admin_operation' ? '공통 운영표에 맞춰 이 관리자 기기에서만 재생됩니다.' : '브라우저를 열어두면 시간표에 맞춰 내 기기에서만 재생됩니다.');
        }else{
            setState('대기');
            setStatus('처음 한 번은 음악 알림 켜기를 눌러주세요.');
        }
    }

    async function unlockAudio(){
        if(!audio || audioUnlocked) return true;
        const oldSrc = audio.getAttribute('src') || '';
        const oldVolume = audio.volume;
        try{
            audio.volume = 0;
            audio.src = 'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQQAAAAAAA==';
            await audio.play();
            audio.pause();
            audio.currentTime = 0;
            audio.src = oldSrc;
            audio.volume = oldVolume;
            audioUnlocked = true;
            return true;
        }catch(e){
            audio.volume = oldVolume;
            if(oldSrc) audio.src = oldSrc;
            return false;
        }
    }

    function sortByPriority(list){
        return list.sort(function(a,b){
            const pa = normalizePriority(a.priority), pb = normalizePriority(b.priority);
            if(pa !== pb) return pa - pb;
            const da = typeof a.diff === 'number' ? a.diff : secondsUntilStart(a);
            const db = typeof b.diff === 'number' ? b.diff : secondsUntilStart(b);
            if(da !== db) return da - db;
            return (a.id||0) - (b.id||0);
        });
    }

    async function loadSchedule(){
        if(!cfg.apiSchedule) return;
        try{
            const res = await fetch(cfg.apiSchedule + '?_=' + Date.now(), {credentials:'same-origin', cache:'no-store'});
            const json = await res.json();
            if(!json.ok){ throw new Error(json.message || 'load_failed'); }
            settings = Object.assign(settings, json.settings || {});
            settings.single_window_seconds = Math.max(30, Math.min(600, parseInt(settings.single_window_seconds || 90, 10)));
            settings.fadeout_seconds = Math.max(0, Math.min(20, parseInt(settings.fadeout_seconds || 4, 10)));
            settings.auto_refresh_seconds = Math.max(15, Math.min(300, parseInt(settings.auto_refresh_seconds || 60, 10)));
            schedules = sortByPriority(Array.isArray(json.items) ? json.items : []);
            blocks = sortByPriority(Array.isArray(json.blocks) ? json.blocks : []);
            if(schedules.concat(blocks).some(function(x){
                if(x.source === 'youtube' || x.youtube_id) return true;
                if(Array.isArray(x.items)) return x.items.some(function(i){ return i.source === 'youtube' || i.youtube_id; });
                return false;
            })) loadYouTubeApi();
            updateRefreshTimer();
            updateButtons();
            updateNext();
            updateListState();
        }catch(e){
            if(nowDesc) nowDesc.textContent = '시간표를 불러오지 못했습니다. 로그인 상태와 네트워크를 확인해주세요.';
        }
    }

    function updateRefreshTimer(){
        const sec = settings.auto_refresh_seconds || 60;
        if(refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(loadSchedule, sec * 1000);
    }

    function isUntimedSet(block){
        return block && (block.kind === 'single_set' || block.kind === 'preview_set');
    }

    function setNowTexts(item, manual, ctx){
        const isBlock = ctx && ctx.block;
        if(nowTitle) nowTitle.textContent = isBlock ? ctx.block.title : (item.title || item.music_title || '하루브금');
        if(nowDesc){
            const sourceLabel = (item.source === 'youtube' || item.youtube_id) ? 'YouTube' : '파일';
            if(isBlock){
                let blockLabel = '시간대 묶음 재생 중 · ';
                if(ctx.block.kind === 'range') blockLabel = '특정 시간 재생 중 · ';
                if(ctx.block.kind === 'single_set') blockLabel = '정각 세트 재생 중 · ';
                if(ctx.block.kind === 'preview_set') blockLabel = '미리듣기 세트 재생 중 · ';
                nowDesc.textContent = blockLabel + sourceLabel+' · '+(item.music_title || '음악')+' ('+(activeBlockIndex+1)+'/'+ctx.block.items.length+')';
            }else{
                nowDesc.textContent = (manual ? '미리듣기 중 · ' : '자동 재생됨 · ') + sourceLabel + ' · ' + (item.music_title || '음악');
            }
        }
    }

    async function playFile(item, finalVol){
        if(!audio || !item.url) throw new Error('file_player_missing');
        pauseYouTube();
        showFilePlayer();
        audio.src = item.url;
        audio.volume = Math.max(0, Math.min(1, finalVol / 100));
        await audio.play();
    }

    async function playYouTube(item, finalVol){
        if(!item.youtube_id) throw new Error('youtube_id_missing');
        if(audio) audio.pause();
        showYouTubePlayer();
        const p = await ensureYouTubePlayer();
        p.setVolume(Math.max(0, Math.min(100, finalVol)));
        p.loadVideoById(item.youtube_id);
        p.playVideo();
    }
    function logPlayback(item, manual, ctx, status, message){
        if(!cfg.apiLog || !item) return;
        try{
            const isBlock = ctx && ctx.block;
            const body = new URLSearchParams();
            body.set('sc_id', isBlock ? (ctx.block.log_id || ctx.block.id || '0') : (item.id || '0'));
            body.set('mf_id', item.music_id || item.mf_id || '0');
            body.set('scope', isBlock ? ((ctx.block.scope || '') + '_block') : (item.scope || ''));
            body.set('action', manual ? (cfg.mode === 'sequence_runner' ? 'manual' : 'preview') : 'auto');
            body.set('status', status || 'success');
            body.set('message', String(message || '').slice(0, 240));
            fetch(cfg.apiLog, {method:'POST', credentials:'same-origin', body}).catch(function(){});
        }catch(e){}
    }


    async function playItem(item, manual, ctx){
        if(!item) return;
        if(isTodayOff() && !manual) return;
        const isBlock = ctx && ctx.block;
        const savedVol = getSavedVolume();
        const itemVol = typeof item.volume === 'number' ? item.volume : parseInt(item.volume || savedVol,10);
        const finalVol = manual ? savedVol : Math.min(savedVol, itemVol);
        setNowTexts(item, manual, ctx);
        try{
            if(item.source === 'youtube' || item.youtube_id) await playYouTube(item, finalVol);
            else await playFile(item, finalVol);
            currentAuto = manual ? null : {priority: normalizePriority(isBlock ? ctx.block.priority : item.priority), kind: isBlock ? 'block' : 'single', id: isBlock ? ctx.block.id : item.id, startedAt: Date.now()};
            setState(manual ? '미리듣기' : (isBlock ? '시간대 재생' : '재생 중'));
            setStatus(manual ? '미리듣기는 내 브라우저에서만 재생됩니다.' : (isBlock ? (ctx.block.kind === 'range' ? '설정된 시간 안에서만 음악을 재생합니다.' : '시간대 안의 음악을 이어서 재생합니다.') : '방금 시간표 음악을 재생했습니다.'));
            logPlayback(item, manual, ctx, 'success', '재생 시작');
            updateListState();
        }catch(e){
            logPlayback(item, manual, ctx, 'fail', e && e.message ? e.message : 'play_failed');
            if(isBlock) stopActiveBlock();
            setState('소리 허용 필요');
            setStatus('브라우저가 자동재생을 막았거나 YouTube 임베드가 제한되었습니다. 음악 알림 켜기를 한 번 더 누르거나 파일 음악으로 테스트해주세요.');
            if(nowDesc) nowDesc.textContent = '재생을 시작하지 못했습니다. 소리 허용/유튜브 임베드 가능 여부를 확인해주세요.';
        }
    }

    function startBlock(block){
        if(!block || !Array.isArray(block.items) || !block.items.length) return;
        if(!isUntimedSet(block) && !block.repeat && isBlockDone(block)) return;
        activeBlock = block;
        activeBlockIndex = block.mode === 'random' ? chooseBlockIndex(block, -1) : 0;
        playItem(block.items[activeBlockIndex], false, {block:block});
    }

    function startSingleSet(item){
        const items = Array.isArray(item.items) && item.items.length ? item.items : [item];
        const block = {
            kind: 'single_set',
            id: 'single_set_' + (item.id || Date.now()),
            log_id: item.id || 0,
            scope: item.scope || '',
            priority: item.priority,
            title: item.title || item.music_title || '정각 세트',
            start: item.time || null,
            end: null,
            mode: 'sequence',
            repeat: 0,
            items: items
        };
        startBlock(block);
    }

    function playNextBlockTrack(){
        if(!activeBlock){ currentAuto = null; return; }
        if(!isUntimedSet(activeBlock) && !blockContainsNow(activeBlock)){
            markBlockDone(activeBlock);
            stopActiveBlock('시간대가 끝나 다음 곡을 재생하지 않습니다.');
            setState(soundReady ? '켜짐' : '대기');
            return;
        }
        const next = chooseBlockIndex(activeBlock, activeBlockIndex);
        if(next < 0){
            if(!isUntimedSet(activeBlock)) markBlockDone(activeBlock);
            stopActiveBlock(isUntimedSet(activeBlock) ? (activeBlock.kind === 'preview_set' ? '미리듣기 세트의 모든 음악을 재생했습니다.' : '정각 세트의 모든 음악을 재생했습니다.') : '시간대 묶음의 모든 곡을 재생했습니다.');
            setState(soundReady ? '켜짐' : '대기');
            return;
        }
        activeBlockIndex = next;
        playItem(activeBlock.items[activeBlockIndex], !!activeBlock.manual, {block:activeBlock});
    }

    function handleMediaEnded(){
        if(activeBlock) playNextBlockTrack();
        else currentAuto = null;
    }

    function dueSingles(){
        const ns = nowSec();
        const win = settings.single_window_seconds || 90;
        const out = [];
        schedules.forEach(function(item){
            const target = secOf(item.time);
            let diff = ns - target;
            if(diff < 0) diff += 86400;
            if(diff < 0 || diff > win) return;
            if(isPlayed(item)) return;
            item.diff = diff;
            out.push(item);
        });
        return sortByPriority(out);
    }

    function runningBlocks(){
        return sortByPriority(blocks.filter(function(block){
            if(!blockContainsNow(block)) return false;
            if(!block.repeat && isBlockDone(block)) return false;
            return Array.isArray(block.items) && block.items.length > 0;
        }));
    }

    function checkDue(){
        if(!soundReady || isTodayOff()) return;

        if(activeBlock && isUntimedSet(activeBlock)){
            return;
        }

        if(activeBlock && !blockContainsNow(activeBlock)){
            if(settings.block_end_action === 'finish_current'){
                activeBlock._ending = true;
                setStatus('시간대가 끝났습니다. 현재 곡까지만 재생하고 멈춥니다.');
            }else{
                fadeOutAndStop(settings.fadeout_seconds, '시간대가 끝나 페이드아웃 후 정지했습니다.');
                stopActiveBlock();
            }
        }

        const singles = dueSingles();
        const rBlocks = activeBlock && !activeBlock._ending ? [activeBlock] : runningBlocks();
        const blockCandidate = rBlocks.length ? rBlocks[0] : null;
        const singleCandidate = singles.length ? singles[0] : null;
        if(!blockCandidate && !singleCandidate) return;

        const candidates = [];
        if(blockCandidate) candidates.push(Object.assign({candidateType:'block'}, blockCandidate));
        if(singleCandidate) candidates.push(Object.assign({candidateType:'single'}, singleCandidate));
        sortByPriority(candidates);
        const chosen = candidates[0];

        if(isMediaPlaying() && currentAuto){
            if(normalizePriority(chosen.priority) >= normalizePriority(currentAuto.priority)) return;
            fadeOutAndStop(0);
            if(activeBlock) stopActiveBlock();
        }

        if(chosen.candidateType === 'single'){
            // 같은 허용 범위 안에 걸린 낮은 우선순위 정각곡은 오늘 재생 처리해서 연속 폭주를 막습니다.
            singles.forEach(markPlayed);
            if(Array.isArray(singleCandidate.items) && singleCandidate.items.length > 1){
                startSingleSet(singleCandidate);
            }else{
                playItem(singleCandidate, false);
            }
            return;
        }

        if(activeBlock && activeBlock.id === blockCandidate.id) return;
        if(blockCandidate.repeat) clearBlockDone(blockCandidate);
        startBlock(blockCandidate);
    }

    function updateNext(){
        const d = new Date();
        if(clockEl) clockEl.textContent = hms(d);
        if(isTodayOff()){ if(countdownEl) countdownEl.textContent = '오늘 꺼짐'; return; }
        if(activeBlock && (isUntimedSet(activeBlock) || blockContainsNow(activeBlock))){ if(countdownEl) countdownEl.textContent = activeBlock.kind === 'preview_set' ? '미리듣기 세트 재생 중' : (activeBlock.kind === 'single_set' ? '정각 세트 재생 중' : '시간대 재생 중'); return; }
        const candidates = [];
        schedules.forEach(function(item){ if(!isPlayed(item)) candidates.push({type:'single', time:item.time, priority:item.priority}); });
        blocks.forEach(function(block){
            if(!Array.isArray(block.items) || !block.items.length) return;
            if(blockContainsNow(block) && (block.repeat || !isBlockDone(block))){ candidates.push({type:'block', time:null, now:true, priority:block.priority}); }
            else if(block.repeat || !isBlockDone(block)){ candidates.push({type:'block', time:block.start, priority:block.priority}); }
        });
        if(!candidates.length){ if(countdownEl) countdownEl.textContent = '오늘 없음'; return; }
        const nowBlock = candidates.find(function(c){ return c.now; });
        if(nowBlock){ if(countdownEl) countdownEl.textContent = '시간대 시작 가능'; return; }
        let remain = 86400;
        candidates.forEach(function(c){ const diff = secondsUntilStart(c); if(diff < remain) remain = diff; });
        if(countdownEl) countdownEl.textContent = fmtRemain(remain);
    }

    function updateListState(){
        if(!todayList) return;
        const ns = nowSec();
        document.querySelectorAll('.hb-schedule-item').forEach(function(el){
            const isBlockEl = el.classList.contains('hb-block-schedule');
            el.classList.remove('is-past','is-next','is-now','is-active-block');
            if(isBlockEl){
                const st = secOf(el.getAttribute('data-start') || '00:00');
                const en = secOf(el.getAttribute('data-end') || '00:00');
                let inRange = false;
                if(en > st) inRange = ns >= st && ns < en;
                else if(en < st) inRange = ns >= st || ns < en;
                if(inRange){ el.classList.add('is-active-block'); return; }
                if(en > st && ns > en) el.classList.add('is-past');
                return;
            }
            const itemTime = el.getAttribute('data-time') || '00:00';
            const target = secOf(itemTime);
            if(Math.abs(ns - target) <= 60){ el.classList.add('is-now'); }
            else if(ns > target){ el.classList.add('is-past'); }
        });
        let nextEl = null;
        let remain = 86400;
        document.querySelectorAll('.hb-schedule-item').forEach(function(el){
            if(el.classList.contains('is-active-block')) return;
            const t = el.classList.contains('hb-block-schedule') ? (el.getAttribute('data-start') || '00:00') : (el.getAttribute('data-time') || '00:00');
            const diff = (secOf(t) - ns + 86400) % 86400;
            if(diff < remain){ remain = diff; nextEl = el; }
        });
        if(nextEl) nextEl.classList.add('is-next');
    }

    if(enableBtn){
        enableBtn.addEventListener('click', async function(){
            soundReady = true;
            localStorage.setItem(storageKey('sound_ready'),'1');
            await unlockAudio();
            loadYouTubeApi();
            updateButtons();
            setTimeout(checkDue, 200);
        });
    }
    if(todayOffBtn){
        todayOffBtn.addEventListener('click', function(){
            setTodayOff(!isTodayOff());
            if(isTodayOff()){ stopMedia(true); stopActiveBlock(); }
            updateNext();
        });
    }
    if(stopBtn){
        stopBtn.addEventListener('click', function(){
            stopMedia(true);
            stopActiveBlock();
            setState(soundReady ? '켜짐' : '대기');
            setStatus('재생을 정지했습니다. 시간표 자동재생 설정은 유지됩니다.');
        });
    }
    if(volume){
        volume.addEventListener('input', function(){
            localStorage.setItem(storageKey('volume'), volume.value);
            if(volumeText) volumeText.textContent = volume.value + '%';
            const v = parseInt(volume.value,10);
            if(audio) audio.volume = v/100;
            try{ if(ytPlayer && ytReady) ytPlayer.setVolume(v); }catch(e){}
        });
    }
    function parsePreviewItems(btn){
        const raw = btn.getAttribute('data-items') || '';
        if(raw){
            try{
                const parsed = JSON.parse(raw);
                if(Array.isArray(parsed)){
                    const out = parsed.filter(function(item){ return item && (item.url || item.youtube_id); }).map(function(item){
                        item.priority = 0;
                        item.title = item.title || btn.dataset.title || item.music_title || '미리듣기';
                        return item;
                    });
                    if(out.length) return out;
                }
            }catch(e){}
        }
        return [{
            source: btn.dataset.source || 'file',
            url: btn.dataset.src || '',
            youtube_id: btn.dataset.youtubeId || '',
            title: btn.dataset.title,
            music_title: btn.dataset.title,
            volume: parseInt(btn.dataset.volume||'80',10),
            priority: 0
        }];
    }

    function startPreviewSet(title, items){
        items = Array.isArray(items) ? items : [];
        items = items.filter(function(item){ return item && (item.url || item.youtube_id); });
        if(!items.length) return;
        if(items.length === 1){
            playItem(items[0], true);
            return;
        }
        stopMedia(true);
        stopActiveBlock();
        activeBlock = {
            kind: 'preview_set',
            manual: true,
            id: 'preview_' + Date.now(),
            log_id: 0,
            scope: 'preview',
            priority: 0,
            title: title || '미리듣기 세트',
            start: null,
            end: null,
            mode: 'sequence',
            repeat: 0,
            items: items
        };
        activeBlockIndex = 0;
        playItem(activeBlock.items[activeBlockIndex], true, {block:activeBlock});
    }

    document.querySelectorAll('.hb-mini-play').forEach(function(btn){
        btn.addEventListener('click', function(){
            if(btn.dataset.confirm === '1' && !confirm('이 항목을 지금 재생할까요?')) return;
            document.querySelectorAll('.hb-manual-current').forEach(function(x){ x.classList.remove('hb-manual-current'); });
            const row = btn.closest('.hb-schedule-item, .hb-sequence-step');
            if(row) row.classList.add('hb-manual-current');
            soundReady = true;
            localStorage.setItem(storageKey('sound_ready'),'1');
            updateButtons();
            startPreviewSet(btn.dataset.title || '미리듣기', parsePreviewItems(btn));
        });
    });
    if(audio){ audio.addEventListener('ended', handleMediaEnded); }

    applySavedVolume();
    updateButtons();
    loadSchedule().then(function(){ checkDue(); });
    setInterval(function(){
        updateNext();
        updateListState();
        checkDue();
    }, 1000);
    document.addEventListener('visibilitychange', function(){
        if(!document.hidden){ loadSchedule().then(function(){ setTimeout(checkDue, 300); }); }
    });
})();
