(function(){
    const cfg = window.HARU_BGM || {};
    const storagePrefix = cfg.storagePrefix || 'haru_bgm_';
    function storageKey(name){ return storagePrefix + name; }
    function storageGet(key){ try{ return window.localStorage ? localStorage.getItem(key) : null; }catch(e){ return null; } }
    function storageSet(key, value){ try{ if(window.localStorage) localStorage.setItem(key, value); return true; }catch(e){ return false; } }
    function storageRemove(key){ try{ if(window.localStorage) localStorage.removeItem(key); }catch(e){} }
    function cleanupOldDailyStorage(maxAgeDays){
        maxAgeDays = Math.max(2, parseInt(maxAgeDays || 14, 10));
        try{
            if(!window.localStorage) return;
            const cutoff = new Date(); cutoff.setHours(0,0,0,0); cutoff.setDate(cutoff.getDate() - maxAgeDays);
            const patterns = ['played_','block_done_','today_off_'];
            const remove = [];
            for(let i=0;i<localStorage.length;i++){
                const key = localStorage.key(i); if(!key || key.indexOf(storagePrefix)!==0) continue;
                const tail = key.slice(storagePrefix.length);
                if(!patterns.some(function(prefix){ return tail.indexOf(prefix)===0; })) continue;
                const m = tail.match(/^(?:played_|block_done_|today_off_)(\d{4}-\d{2}-\d{2})(?:_|$)/);
                if(!m) continue;
                const d = new Date(m[1]+'T00:00:00');
                if(Number.isFinite(d.getTime()) && d < cutoff) remove.push(key);
            }
            remove.forEach(function(key){ storageRemove(key); });
        }catch(e){}
    }
    const root = (cfg.mode === 'sitewide' ? document.getElementById('haruBgmSitewide') : document.getElementById('haruBgmAdminApp')) || document;
    function hbFind(id){ return root && root.querySelector ? root.querySelector('#' + id) : document.getElementById(id); }
    const audio = hbFind('hbAudio');
    const ytWrap = hbFind('hbYoutubeWrap');
    const ytNode = hbFind('hbYouTubePlayer');
    const enableBtn = hbFind('hbEnableSound');
    const todayOffBtn = hbFind('hbTodayOff');
    const stopBtn = hbFind('hbStopSound');
    const volumeToggleBtn = hbFind('hbVolumeToggle');
    const volumeRow = hbFind('hbVolumeRow');
    const clockEl = hbFind('hbClock');
    const countdownEl = hbFind('hbCountdown');
    const nowTitle = hbFind('hbNowTitle');
    const nowDesc = hbFind('hbNowDesc');
    const soundState = hbFind('hbSoundState');
    const volume = hbFind('hbVolume');
    const volumeText = hbFind('hbVolumeText');
    const todayList = hbFind('hbTodayList');
    const statusText = hbFind('hbStatusText');
    const policyText = hbFind('hbPolicyText');
    const ytPlayerElementId = ytNode ? ('hbYouTubePlayer_' + Math.random().toString(36).slice(2, 10)) : '';
    if(ytNode) ytNode.id = ytPlayerElementId;

    let settings = {
        priority_mode: 'single_first',
        priority_label: '정각 시간표 우선',
        single_window_seconds: 90,
        fadeout_seconds: 4,
        block_end_action: 'fade_stop',
        auto_refresh_seconds: 60
    };
    let schedules = [];
    let blocks = [];
    let soundReady = storageGet(storageKey('sound_ready')) === '1';
    let audioUnlocked = false;
    let activeBlock = null;
    let activeBlockIndex = -1;
    let currentAuto = null;
    let currentItem = null;
    let currentManual = false;
    let refreshTimer = null;
    let fadeTimer = null;
    let mediaGeneration = 0;
    let audioActiveGeneration = 0;
    let ytPlayer = null;
    let ytReady = false;
    let ytApiPromise = null;
    let ytPlayerPromise = null;
    let ytEndingLock = false;
    let ytActiveGeneration = 0;
    let ytExpectedGeneration = 0;
    let ytExpectedVideoId = '';
    let ytPlayWait = null;
    let ytLastError = null;
    const retryAt = Object.create(null);
    let debugBadge = null;
    let serverClockOffsetMs = 0;
    let serverClockSynced = false;
    let lastScheduleDate = dateKey();
    let broadcastState = {mode:'auto', revision:0, started_epoch_ms:0, seek_seconds:0, item:null};
    let currentBroadcastRevision = 0;
    let localPreviewOverride = false;
    function readRevisionFlag(name){
        const value = parseInt(storageGet(storageKey(name)) || '0', 10);
        return Number.isFinite(value) && value > 0 ? value : 0;
    }
    function writeRevisionFlag(name, revision){
        const value = Math.max(0, parseInt(revision || 0, 10) || 0);
        if(value) storageSet(storageKey(name), String(value)); else storageRemove(storageKey(name));
        return value;
    }
    let localBroadcastStoppedRevision = readRevisionFlag('broadcast_stopped_revision');
    let broadcastEndedRevision = readRevisionFlag('broadcast_ended_revision');
    let broadcastRequestSeq = 0;
    let broadcastAcceptedRequestSeq = 0;
    let scheduleRequestSeq = 0;
    let scheduleAcceptedRequestSeq = 0;
    let broadcastPollTimer = null;
    let broadcastApplyKey = '';
    let broadcastApplyPromise = null;
    let scheduleRetryTimer = null;
    let scheduleRetryStep = 0;
    let scheduleBootstrapped = false;
    const singlePlayInFlight = Object.create(null);
    const isSitewideMode = cfg.mode === 'sitewide';
    const playbackTabId = 'hb_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
    const playbackLeaderKey = storageKey('playback_leader');
    const playbackLeaderLeaseMs = 6500;
    let playbackLeaderHeartbeat = null;

    function updateSitewideSpacer(){
        // v1.5.42까지는 재생바(position:fixed) 아래에 실제 콘텐츠가 가려지지 않도록
        // 문서 맨 끝에 빈 스페이서 요소를 넣고 그 높이를 재생바 실측 크기로 맞춰왔습니다.
        // 계산 자체는 정확했지만, 그 결과로 페이지 맨 아래에 항상 재생바 높이만큼의
        // 빈 공간이 남아 있는 것 자체가 부자연스러워 보인다는 피드백에 따라
        // 스페이서를 아예 없앴습니다. 이 함수는 다른 곳의 호출부를 남겨두기 위한
        // 빈 함수로만 남겨둡니다.
    }

    function updateSitewideCollisionOffset(){
        if(!isSitewideMode || !root || !root.getBoundingClientRect || !document.body) return;
        try{
            // 채팅/쿠키/PWA처럼 다른 위젯이 동적으로 나타나는 사이트에서도
            // 고정 플레이어가 화면 하단 위젯을 가리지 않도록 필요한 만큼만 올립니다.
            root.style.setProperty('--hb-sitewide-bottom-offset', '0px');
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
            const baseRect = root.getBoundingClientRect();
            let offset = 0;
            const nodes = document.querySelectorAll('[id], [class]');
            for(let i=0; i<nodes.length && i<600; i++){
                const node = nodes[i];
                if(node === root || root.contains(node) || !node.getBoundingClientRect) continue;
                // RB빌더 등 그누보드 커스텀 테마가 모바일 화면 하단에 상시 붙여두는
                // 자체 네비게이션 바(tail_fixed_gnb 등)는 항상 떠 있는 사이트 뼈대이지,
                // 일시적으로 나타나는 위젯이 아닙니다. 이런 요소까지 "충돌"로 판정하면
                // 재생바가 거의 모든 페이지에서 필요 이상으로 밀려 올라가고, 그 값이
                // 스페이서 높이에도 그대로 반영되어 스크롤 맨 아래에 빈 공간이 남습니다.
                if(node.classList && node.classList.contains('tail_fixed_gnb')) continue;
                const style = window.getComputedStyle ? window.getComputedStyle(node) : null;
                if(!style || (style.position !== 'fixed' && style.position !== 'sticky')) continue;
                if(style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity || '1') === 0) continue;
                const rect = node.getBoundingClientRect();
                if(rect.width < 2 || rect.height < 2 || rect.top >= viewportHeight || rect.bottom <= 0) continue;
                // 재생바 바로 아래쪽(자기 높이의 절반 이내)까지 실제로 침범한 요소만 "근처"로 봅니다.
                // 이전에는 재생바 높이의 2배 가까운 범위를 전부 근처로 잡아 화면 하단에 있는
                // 무관한 위젯(리빌더 설정바 등)까지 충돌로 오판해 재생바를 필요 이상으로
                // 밀어 올리고, 그 밀어 올린 만큼이 스페이서 높이에도 그대로 반영되어
                // 스크롤 맨 아래에 원인 모를 빈 공간이 남았습니다.
                const nearBottom = rect.top >= viewportHeight - (baseRect.height / 2 + 20);
                if(!nearBottom) continue;
                // 가로로 "조금이라도" 겹치면 충돌로 보지 않고, 겹치는 폭이 재생바 폭의
                // 상당 부분(30% 이상)을 차지할 때만 실제 충돌로 인정합니다.
                const overlapLeft = Math.max(rect.left, baseRect.left);
                const overlapRight = Math.min(rect.right, baseRect.right);
                const overlapWidth = Math.max(0, overlapRight - overlapLeft);
                if(overlapWidth < baseRect.width * 0.3) continue;
                offset = Math.max(offset, baseRect.bottom - rect.top + 12);
            }
            // 계산된 오프셋에도 상한을 둬서, 오탐이 남아 있더라도 재생바가 화면 절반 가까이
            // 위로 튀어 오르는 극단적인 결과는 방지합니다.
            const maxOffset = Math.max(80, baseRect.height);
            root.style.setProperty('--hb-sitewide-bottom-offset', Math.max(0, Math.min(maxOffset, Math.ceil(offset))) + 'px');
            updateSitewideSpacer();
        }catch(e){}
    }

    function updateDebugBadge(){
        const show = parseInt(settings.show_debug_badge || 0, 10) === 1;
        if(!show){
            if(debugBadge && debugBadge.parentNode) debugBadge.parentNode.removeChild(debugBadge);
            debugBadge = null;
            return;
        }
        if(!debugBadge){
            debugBadge = document.createElement('div');
            debugBadge.id = 'hbDebugBadge';
            debugBadge.style.cssText = 'position:fixed;right:10px;bottom:10px;z-index:99999;padding:7px 10px;border:1px solid rgba(0,0,0,.15);border-radius:10px;background:rgba(255,255,255,.94);font:12px/1.35 ui-monospace,SFMono-Regular,Consolas,monospace;color:#333;box-shadow:0 4px 16px rgba(0,0,0,.12);pointer-events:none';
            document.body.appendChild(debugBadge);
        }
        const active = activeBlock ? ((activeBlock.kind || 'block') + ':' + (activeBlock.id || '0')) : '-';
        debugBadge.textContent = 'HB ' + (cfg.mode || 'sitewide') + ' · sound:' + (soundReady ? 'on' : 'wait') + ' · schedules:' + schedules.length + ' · blocks:' + blocks.length + ' · active:' + active;
    }

    function pad(n){ return String(n).padStart(2,'0'); }
    function clockNow(){ return new Date(Date.now() + serverClockOffsetMs); }
    function syncServerClock(serverTime, requestStartedAt, serverEpochMs){
        const end = Date.now();
        const midpoint = requestStartedAt ? (Number(requestStartedAt) + end) / 2 : end;
        // 편성은 그누보드 서버의 벽시계 기준입니다. epoch만 쓰면 Date#getHours()가
        // 방문자 브라우저의 현지 시간대로 변환되어 해외 방문자에서 편성 시각이 어긋납니다.
        // 서버의 YYYY-MM-DD HH:MM:SS를 UTC 구성요소에 담은 가상 벽시계로 해석해
        // 브라우저 시간대와 DST에 관계없이 모든 편성 계산이 서버 벽시계를 그대로 보도록 맞춥니다.
        const m = String(serverTime || '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
        if(m){
            const wallMs = Date.UTC(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10), parseInt(m[4],10), parseInt(m[5],10), parseInt(m[6],10));
            serverClockOffsetMs = wallMs - midpoint;
            serverClockSynced = true;
            return;
        }
        // 구버전 API 호환용 fallback. server_time이 있으면 위 벽시계 경로를 항상 우선합니다.
        const epoch = Number(serverEpochMs || 0);
        if(Number.isFinite(epoch) && epoch > 0){
            serverClockOffsetMs = epoch - midpoint;
            serverClockSynced = true;
        }
    }
    function dateKey(d){ d = d || clockNow(); return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate()); }
    function hms(d){ return pad(d.getUTCHours())+':'+pad(d.getUTCMinutes())+':'+pad(d.getUTCSeconds()); }
    function secOf(hmText){ const p=String(hmText||'00:00').split(':'); return parseInt(p[0]||'0',10)*3600+parseInt(p[1]||'0',10)*60; }
    function nowSec(){ const d=clockNow(); return d.getUTCHours()*3600+d.getUTCMinutes()*60+d.getUTCSeconds(); }
    function fmtRemain(sec){ if(sec < 0) sec += 86400; const h=Math.floor(sec/3600); const m=Math.floor((sec%3600)/60); const s=sec%60; return (h? h+'시간 ':'')+m+'분 '+s+'초'; }
    function serviceDateOf(item){ return String((item && item.service_date) || dateKey()); }
    function todayOffKey(){ return storageKey('today_off_'+dateKey()); }
    function isTodayOff(){ return storageGet(todayOffKey()) === '1'; }
    function setTodayOff(v){ if(v){ storageSet(todayOffKey(), '1'); }else{ storageRemove(todayOffKey()); } updateButtons(); }
    function playbackLeaderState(){
        if(!isSitewideMode) return {id:playbackTabId, expires:Date.now()+playbackLeaderLeaseMs};
        const raw = storageGet(playbackLeaderKey);
        if(!raw) return null;
        try{
            const v = JSON.parse(raw);
            if(!v || !v.id || !Number.isFinite(Number(v.expires))) return null;
            return {id:String(v.id), expires:Number(v.expires)};
        }catch(e){ return null; }
    }
    function claimPlaybackLeader(force){
        if(!isSitewideMode) return true;
        const now = Date.now();
        const current = playbackLeaderState();
        if(force || !current || current.expires <= now || current.id === playbackTabId){
            storageSet(playbackLeaderKey, JSON.stringify({id:playbackTabId, expires:now + playbackLeaderLeaseMs}));
            const verify = playbackLeaderState();
            return !!verify && verify.id === playbackTabId;
        }
        return false;
    }
    function isPlaybackLeader(){
        if(!isSitewideMode) return true;
        const current = playbackLeaderState();
        return !!current && current.id === playbackTabId && current.expires > Date.now();
    }
    function releasePlaybackLeader(){
        if(!isSitewideMode) return;
        const current = playbackLeaderState();
        if(current && current.id === playbackTabId) storageRemove(playbackLeaderKey);
    }
    function canThisTabPlay(){ return !isSitewideMode || isPlaybackLeader() || claimPlaybackLeader(false); }
    function singleFlightKey(item){ return serviceDateOf(item) + ':' + String(item && item.id || '0') + ':' + String(item && item.time || ''); }
    function singleIsInFlight(item){ return !!singlePlayInFlight[singleFlightKey(item)]; }
    function runSingleCandidate(item){
        if(!item) return Promise.resolve(false);
        const key = singleFlightKey(item);
        if(singlePlayInFlight[key]) return singlePlayInFlight[key];
        item._suppressSingles = [item];
        const task = Promise.resolve(Array.isArray(item.items) && item.items.length > 1
            ? startSingleSet(item)
            : playItem(item, false, {suppressSingles:[item]}));
        singlePlayInFlight[key] = task.then(function(value){
            if(singlePlayInFlight[key] === wrapped) delete singlePlayInFlight[key];
            return value;
        }, function(err){
            if(singlePlayInFlight[key] === wrapped) delete singlePlayInFlight[key];
            throw err;
        });
        const wrapped = singlePlayInFlight[key];
        return wrapped;
    }
    function playedKey(item){ return storageKey('played_'+serviceDateOf(item)+'_'+(item.id||'0')+'_'+(item.time||'')); }
    function isPlayed(item){ return storageGet(playedKey(item)) === '1'; }
    function markPlayed(item){ storageSet(playedKey(item), '1'); }
    function retryKey(kind, item){ return kind+'_'+(kind === 'block' ? blockDoneKey(item) : playedKey(item)); }
    function retryReady(kind, item){ const t = retryAt[retryKey(kind,item)] || 0; return Date.now() >= t; }
    function deferRetry(kind, item, ms){ retryAt[retryKey(kind,item)] = Date.now() + Math.max(1000, parseInt(ms || 5000,10)); }
    function clearRetry(kind, item){ delete retryAt[retryKey(kind,item)]; }
    function manualStopKey(){ return storageKey('manual_stop'); }
    function readManualStop(){
        const raw = storageGet(manualStopKey());
        if(!raw) return null;
        try{
            const v=JSON.parse(raw);
            if(!v || !v.kind || !v.id) return null;
            if(v.saved_at && Date.now() - Number(v.saved_at) > 36*3600*1000){ clearManualStop(); return null; }
            return v;
        }catch(e){ return null; }
    }
    function setManualStop(auto){
        if(!auto){ storageRemove(manualStopKey()); return; }
        storageSet(manualStopKey(), JSON.stringify({service_date:String(auto.serviceDate || auto.service_date || dateKey()),kind:String(auto.kind||''),id:String(auto.id||''),saved_at:Date.now()}));
    }
    function clearManualStop(){ storageRemove(manualStopKey()); }
    function isManualStoppedCandidate(candidate){
        const stopped = readManualStop();
        if(!stopped || !candidate) return false;
        const kind = candidate.candidateType === 'block' ? 'block' : 'single';
        return stopped.kind === kind && stopped.id === String(candidate.id || '') && String(stopped.service_date || '') === serviceDateOf(candidate);
    }
    function blockDoneKey(block){ return storageKey('block_done_'+serviceDateOf(block)+'_'+(block.id||'0')); }
    function isBlockDone(block){ return storageGet(blockDoneKey(block)) === '1'; }
    function markBlockDone(block){ storageSet(blockDoneKey(block), '1'); }
    function clearBlockDone(block){ storageRemove(blockDoneKey(block)); }
    function setState(text){ if(soundState) soundState.textContent = text; }
    function setStatus(text){ if(statusText) statusText.textContent = text; }
    function normalizePriority(x){ const n = parseInt(x, 10); return Number.isFinite(n) ? n : 999; }
    function getSavedVolume(){ return volume ? Math.max(0, Math.min(100, parseInt(volume.value || '80', 10))) : 80; }

    function ymdFromDate(d){ return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate()); }
    function addDays(d, amount){ const out = new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())); out.setUTCDate(out.getUTCDate()+amount); return out; }
    function dayAllowed(block, d){
        const days = String(block && block.days != null ? block.days : '0,1,2,3,4,5,6').split(',');
        return days.indexOf(String(d.getUTCDay())) !== -1;
    }
    function dateAllowed(block, d){
        const ymd = ymdFromDate(d);
        const start = String((block && block.start_date) || '');
        const end = String((block && block.end_date) || '');
        if(start && start !== '0000-00-00' && ymd < start) return false;
        if(end && end !== '0000-00-00' && ymd > end) return false;
        return true;
    }
    function blockServiceDate(block, now){
        now = now || clockNow();
        const ns = now.getUTCHours()*3600+now.getUTCMinutes()*60+now.getUTCSeconds();
        const st = secOf(block.start), en = secOf(block.end);
        if(en < st && ns < en) return addDays(now, -1);
        return new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate()));
    }
    function blockContainsNow(block){
        if(!block) return false;
        const now = clockNow();
        const ns = now.getUTCHours()*3600+now.getUTCMinutes()*60+now.getUTCSeconds();
        const st = secOf(block.start);
        const en = secOf(block.end);
        if(st === en) return false;
        let inTime = false;
        if(en > st) inTime = ns >= st && ns < en;
        else inTime = ns >= st || ns < en;
        if(!inTime) return false;
        const serviceDate = blockServiceDate(block, now);
        return dayAllowed(block, serviceDate) && dateAllowed(block, serviceDate);
    }


    function sitewideResumeEnabled(){ return cfg.mode === 'sitewide' || cfg.persistAcrossPages === true; }
    function resumeStorageKey(){ return storageKey('resume_v1'); }
    function clearSitewideResume(){ if(sitewideResumeEnabled()) storageRemove(resumeStorageKey()); }
    function currentMediaTime(){
        try{
            if(currentItem && (currentItem.source === 'youtube' || currentItem.youtube_id) && ytPlayer && ytReady) return Math.max(0, Number(ytPlayer.getCurrentTime() || 0));
            if(audio && Number.isFinite(audio.currentTime)) return Math.max(0, Number(audio.currentTime || 0));
        }catch(e){}
        return 0;
    }
    function saveSitewideResume(){
        if(!sitewideResumeEnabled() || !soundReady || isTodayOff() || currentManual || !currentAuto || !currentItem || !isMediaPlaying()) return;
        const state = {
            v:1, saved_at:Date.now(), position:currentMediaTime(),
            item:{
                id:currentItem.id || 0, music_id:currentItem.music_id || currentItem.mf_id || 0,
                source:currentItem.source || (currentItem.youtube_id ? 'youtube' : 'file'),
                url:currentItem.url || '', youtube_id:currentItem.youtube_id || '',
                title:currentItem.title || '', music_title:currentItem.music_title || '', volume:currentItem.volume || 80
            },
            auto:{kind:currentAuto.kind || 'single', id:currentAuto.id || 0, priority:currentAuto.priority || 999, service_date:currentAuto.serviceDate || serviceDateOf(currentItem)},
            block:activeBlock ? {id:activeBlock.id || 0, kind:activeBlock.kind || 'block', index:activeBlockIndex, log_id:activeBlock.log_id || 0, service_date:serviceDateOf(activeBlock), played_indexes:activeBlock._playedIndexes ? Object.keys(activeBlock._playedIndexes).map(function(x){ return parseInt(x,10); }).filter(Number.isFinite) : []} : null
        };
        try{ storageSet(resumeStorageKey(), JSON.stringify(state)); }catch(e){}
    }
    function readSitewideResume(){
        if(!sitewideResumeEnabled()) return null;
        const raw = storageGet(resumeStorageKey());
        if(!raw) return null;
        try{
            const state = JSON.parse(raw);
            if(!state || state.v !== 1 || !state.saved_at || !state.item) return null;
            const age = Date.now() - Number(state.saved_at || 0);
            if(age < 0 || age > 120000){ clearSitewideResume(); return null; }
            state.resume_at = Math.max(0, Number(state.position || 0) + age/1000);
            return state;
        }catch(e){ clearSitewideResume(); return null; }
    }
    async function restoreSitewideResume(){
        if(!sitewideResumeEnabled() || !soundReady || isTodayOff()) return false;
        const state = readSitewideResume();
        if(!state) return false;
        let item = null, ctx = {resume:true, resumeAt:state.resume_at, suppressLog:true};
        if(state.block){
            let b = null;
            if(String(state.block.kind || '') === 'single_set'){
                const scheduleId = String(state.block.log_id || (state.auto && state.auto.id) || '').replace(/^single_set_/, '');
                const single = schedules.find(function(x){ return String(x.id || '') === scheduleId && Array.isArray(x.items) && x.items.length > 1; }) || null;
                if(single){
                    b = {
                        kind:'single_set', id:'single_set_' + single.id, log_id:single.id,
                        scope:single.scope || '', priority:single.priority,
                        title:single.title || single.music_title || '정각 세트', start:single.time || null, end:null,
                        mode:'sequence', repeat:0, items:single.items, service_date:single.service_date || '',
                        _suppressSingles:[single]
                    };
                }
            }else{
                b = blocks.find(function(x){ return String(x.id) === String(state.block.id) && String(x.kind || 'block') === String(state.block.kind || 'block'); }) || null;
            }
            if(!b || (state.block.service_date && serviceDateOf(b) !== String(state.block.service_date)) || (!isUntimedSet(b) && !blockContainsNow(b)) || !Array.isArray(b.items) || !b.items.length){ clearSitewideResume(); return false; }
            activeBlock = b;
            activeBlock._failedIndexes = {};
            activeBlock._playedIndexes = {};
            if(Array.isArray(state.block.played_indexes)) state.block.played_indexes.forEach(function(i){ i=parseInt(i,10); if(Number.isFinite(i) && i>=0 && i<b.items.length) activeBlock._playedIndexes[i]=true; });
            activeBlockIndex = Math.max(0, Math.min(b.items.length-1, parseInt(state.block.index || 0,10)));
            item = b.items[activeBlockIndex];
            ctx.block = b;
        }else{
            const resumeId = String((state.auto && state.auto.id) || (state.item && state.item.id) || '0');
            item = schedules.find(function(x){ return String(x.id || 0) === resumeId; }) || null;
            // 관리자 미리듣기/순서표 상태가 사이트 전체 방송으로 새어 나오지 않게
            // 현재 API에 실제로 존재하는 편성만 이어받습니다.
            if(!item || (state.auto && state.auto.service_date && serviceDateOf(item) !== String(state.auto.service_date))){ clearSitewideResume(); return false; }
            if(state.item && Number.isFinite(Number(state.resume_at))) ctx.resumeAt = state.resume_at;
        }
        if(!item || (!item.url && !item.youtube_id)){ clearSitewideResume(); return false; }
        const ok = await playItem(item, false, ctx);
        if(ok){
            setStatus('페이지 이동 전 재생 위치에서 이어서 재생합니다.');
            return true;
        }
        return false;
    }

    function wallClockTargetMs(serviceDate, timeText){
        const m = String(serviceDate || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if(!m) return NaN;
        const sec = secOf(timeText || '00:00');
        return Date.UTC(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10), Math.floor(sec/3600), Math.floor((sec%3600)/60), sec%60);
    }
    function secondsUntilStart(item){
        const targetMs = wallClockTargetMs(serviceDateOf(item), item.time || item.start);
        if(Number.isFinite(targetMs)){
            const diff = Math.ceil((targetMs - clockNow().getTime()) / 1000);
            if(diff >= 0) return diff;
            if(Math.abs(diff) <= Math.max(60, settings.single_window_seconds || 90)) return 0;
        }
        return (secOf(item.time || item.start) - nowSec() + 86400) % 86400;
    }

    function chooseBlockIndex(block, current){
        const items = Array.isArray(block.items) ? block.items : [];
        if(!items.length) return -1;
        const failed = block && block._failedIndexes ? block._failedIndexes : {};
        const played = block && !block.repeat && block._playedIndexes ? block._playedIndexes : {};
        const available = [];
        for(let i=0;i<items.length;i++) if(!failed[i] && !played[i]) available.push(i);
        if(!available.length) return -1;
        if(block.mode === 'random'){
            let pool = available.filter(function(i){ return i !== current; });
            if(!pool.length) pool = available;
            return pool[Math.floor(Math.random() * pool.length)];
        }
        for(let i=current+1;i<items.length;i++) if(!failed[i] && !played[i]) return i;
        if(block.repeat){
            for(let i=0;i<=current && i<items.length;i++) if(!failed[i] && !played[i]) return i;
        }
        return -1;
    }

    function loadYouTubeApi(){
        if(window.YT && window.YT.Player) return Promise.resolve();
        if(ytApiPromise) return ytApiPromise;
        ytApiPromise = new Promise(function(resolve, reject){
            let settled = false;
            let timer = null;
            const done = function(ok, err){
                if(settled) return;
                settled = true;
                if(timer) clearTimeout(timer);
                if(ok) resolve(); else { ytApiPromise = null; reject(err || new Error('youtube_api_load_failed')); }
            };
            const old = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function(){
                try{ if(typeof old === 'function') old(); }catch(e){}
                done(true);
            };
            let tag = document.querySelector('script[src="https://www.youtube.com/iframe_api"]');
            if(!tag){
                tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                tag.async = true;
                const first = document.getElementsByTagName('script')[0];
                if(first && first.parentNode) first.parentNode.insertBefore(tag, first); else (document.head || document.documentElement).appendChild(tag);
            }
            tag.addEventListener('error', function(){ done(false, new Error('youtube_api_load_failed')); }, {once:true});
            timer = setTimeout(function(){
                if(window.YT && window.YT.Player) done(true); else done(false, new Error('youtube_api_timeout'));
            }, 8000);
        });
        return ytApiPromise;
    }

    function resetYouTubePlayer(){
        ytReady = false;
        ytPlayerPromise = null;
        try{ if(ytPlayer && typeof ytPlayer.destroy === 'function') ytPlayer.destroy(); }catch(e){}
        ytPlayer = null;
    }

    function ensureYouTubePlayer(){
        if(!ytNode || !ytPlayerElementId) return Promise.reject(new Error('youtube_node_missing'));
        if(ytPlayer && ytReady) return Promise.resolve(ytPlayer);
        if(ytPlayerPromise) return ytPlayerPromise;
        ytPlayerPromise = loadYouTubeApi().then(function(){
            return new Promise(function(resolve, reject){
                let settled = false;
                let readyTimer = null;
                let createdPlayer = null;
                const finishReady = function(){
                    if(settled) return;
                    settled = true;
                    if(readyTimer) clearTimeout(readyTimer);
                    ytPlayer = createdPlayer || ytPlayer;
                    ytReady = true;
                    resolve(ytPlayer);
                };
                const failReady = function(err){
                    if(settled) return false;
                    settled = true;
                    if(readyTimer) clearTimeout(readyTimer);
                    try{ if(createdPlayer && typeof createdPlayer.destroy === 'function') createdPlayer.destroy(); }catch(e){}
                    if(ytPlayer === createdPlayer) ytPlayer = null;
                    ytReady = false;
                    reject(err || new Error('youtube_player_ready_failed'));
                    return true;
                };
                readyTimer = setTimeout(function(){ failReady(new Error('youtube_player_ready_timeout')); }, 9000);
                try{
                    createdPlayer = new YT.Player(ytPlayerElementId, {
                        height: '360',
                        width: '100%',
                        videoId: '',
                        playerVars: { playsinline: 1, rel: 0, modestbranding: 1, controls: 1, origin: location.origin },
                        events: {
                            onReady: function(){ finishReady(); },
                            onError: function(e){
                                ytLastError = e && typeof e.data !== 'undefined' ? String(e.data) : 'youtube_player_error';
                                if(!settled){ failReady(new Error('youtube_ready_error_'+ytLastError)); return; }
                                if(ytPlayWait){ const wait = ytPlayWait; ytPlayWait = null; wait.reject(new Error('youtube_error_'+ytLastError)); return; }
                                let videoId = '';
                                try{ videoId = String((ytPlayer && ytPlayer.getVideoData && ytPlayer.getVideoData().video_id) || ''); }catch(err){}
                                if(ytActiveGeneration === mediaGeneration && ytExpectedGeneration === mediaGeneration && (!videoId || videoId === ytExpectedVideoId)) handleMediaError('youtube');
                            },
                            onStateChange: function(e){
                                if(!settled) return;
                                let videoId = '';
                                try{ videoId = String((ytPlayer && ytPlayer.getVideoData && ytPlayer.getVideoData().video_id) || ''); }catch(err){}
                                const expected = ytExpectedGeneration === mediaGeneration && (!videoId || videoId === ytExpectedVideoId);
                                if(e.data === YT.PlayerState.PLAYING && expected) ytActiveGeneration = ytExpectedGeneration;
                                if(ytPlayWait && expected && e.data === YT.PlayerState.PLAYING){
                                    const wait = ytPlayWait; ytPlayWait = null; wait.resolve();
                                }
                                if(e.data === YT.PlayerState.ENDED){
                                    if(!expected || ytActiveGeneration !== mediaGeneration) return;
                                    if(ytEndingLock) return;
                                    ytEndingLock = true;
                                    setTimeout(function(){ ytEndingLock = false; }, 500);
                                    handleMediaEnded();
                                }
                            }
                        }
                    });
                    ytPlayer = createdPlayer;
                }catch(e){ failReady(e); }
            });
        }).catch(function(err){ resetYouTubePlayer(); throw err; });
        return ytPlayerPromise;
    }

    function isYouTubePlaybackStarted(){
        try{
            if(!ytPlayer || !ytReady || !window.YT) return false;
            return ytPlayer.getPlayerState() === YT.PlayerState.PLAYING;
        }catch(e){ return false; }
    }

    function waitForYouTubePlayback(timeoutMs){
        timeoutMs = Math.max(1000, parseInt(timeoutMs || 5000, 10));
        if(isYouTubePlaybackStarted()) return Promise.resolve();
        if(ytPlayWait){ try{ ytPlayWait.reject(new Error('media_replaced')); }catch(e){} ytPlayWait = null; }
        return new Promise(function(resolve, reject){
            const wait = { timer:null, resolve:null, reject:null };
            wait.resolve = function(){ if(wait.timer) clearTimeout(wait.timer); if(ytPlayWait === wait) ytPlayWait = null; resolve(); };
            wait.reject = function(err){ if(wait.timer) clearTimeout(wait.timer); if(ytPlayWait === wait) ytPlayWait = null; reject(err); };
            wait.timer = setTimeout(function(){
                if(ytPlayWait === wait) ytPlayWait = null;
                if(isYouTubePlaybackStarted()) resolve(); else reject(new Error(ytLastError ? 'youtube_error_'+ytLastError : 'youtube_play_timeout'));
            }, timeoutMs);
            ytPlayWait = wait;
        });
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
            return st === YT.PlayerState.PLAYING;
        }catch(e){ return false; }
    }

    function isMediaPlaying(){
        const audioPlaying = audio && !audio.paused && !audio.ended;
        return !!audioPlaying || isYouTubePlaying();
    }

    function stopMedia(reset){
        mediaGeneration++;
        audioActiveGeneration = 0;
        ytExpectedGeneration = 0;
        ytExpectedVideoId = '';
        if(ytPlayWait){ const wait = ytPlayWait; ytPlayWait = null; try{ wait.reject(new Error('media_replaced')); }catch(e){} }
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        if(audio){ audio.pause(); if(reset){ try{ audio.currentTime = 0; }catch(e){} } }
        stopYouTube();
        currentAuto = null;
        if(reset){ currentItem = null; currentManual = false; }
    }

    function fadeOutAndStop(seconds, message){
        seconds = Math.max(0, parseInt(seconds || 0, 10));
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        if(seconds <= 0){ stopMedia(true); if(message) setStatus(message); return; }
        const steps = Math.max(1, seconds * 5);
        const fadeGeneration = mediaGeneration;
        let left = steps;
        const audioStart = audio ? audio.volume : 0;
        let ytStart = getSavedVolume();
        try{ if(ytPlayer && ytReady) ytStart = ytPlayer.getVolume(); }catch(e){}
        fadeTimer = setInterval(function(){
            if(fadeGeneration !== mediaGeneration){ clearInterval(fadeTimer); fadeTimer = null; return; }
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

    function fadeInAudioFrom(targetVolume, seconds){
        // 파일 재생을 최종 볼륨으로 곧장 시작하면, 특히 곡 중간(이어재생)에서 시작할 때
        // 스피커가 "툭" 튀는 팝/클릭 노이즈가 납니다. 아주 짧게 0에서 목표 볼륨까지
        // 올려서 이 노이즈를 없앱니다. fadeOutAndStop과 같은 fadeTimer/세대 관리 방식을 씁니다.
        if(!audio) return;
        seconds = Math.max(0.05, Number(seconds) || 0.35);
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        const steps = Math.max(1, Math.round(seconds * 20));
        const fadeGeneration = mediaGeneration;
        let done = 0;
        audio.volume = 0;
        fadeTimer = setInterval(function(){
            if(fadeGeneration !== mediaGeneration || !audio){ clearInterval(fadeTimer); fadeTimer = null; return; }
            done++;
            const ratio = Math.min(1, done / steps);
            try{ audio.volume = targetVolume * ratio; }catch(e){}
            if(ratio >= 1){
                clearInterval(fadeTimer);
                fadeTimer = null;
            }
        }, Math.max(10, Math.round((seconds * 1000) / steps)));
    }

    function stopActiveBlock(message){
        activeBlock = null;
        activeBlockIndex = -1;
        currentAuto = null;
        if(message) setStatus(message);
        updateDebugBadge();
    }

    function applySavedVolume(){
        if(!volume) return;
        const saved = storageGet(storageKey('volume'));
        if(saved !== null && !Number.isNaN(parseInt(saved,10))) volume.value = Math.max(0, Math.min(100, parseInt(saved,10)));
        if(volumeText) volumeText.textContent = volume.value + '%';
        const vol = parseInt(volume.value,10);
        if(audio) audio.volume = vol/100;
        try{ if(ytPlayer && ytReady) ytPlayer.setVolume(vol); }catch(e){}
    }

    function updateButtons(){
        if(enableBtn){
            enableBtn.textContent = cfg.mode === 'sitewide' ? (soundReady ? '● 켜짐' : '▶ 켜기') : (soundReady ? '● 음악 알림 켜짐' : '▶ 음악 알림 켜기');
            enableBtn.setAttribute('aria-label', soundReady ? 'BGM 소리 켜짐' : 'BGM 소리 켜기');
            enableBtn.classList.toggle('hb-btn-primary', !soundReady);
        }
        if(todayOffBtn){
            todayOffBtn.textContent = cfg.mode === 'sitewide' ? (isTodayOff() ? '○ 해제' : '×') : (isTodayOff() ? '○ 오늘 꺼짐 해제' : '× 오늘만 끄기');
            todayOffBtn.setAttribute('aria-label', isTodayOff() ? '오늘만 끄기 해제' : '오늘만 끄기');
            todayOffBtn.title = isTodayOff() ? '오늘만 끄기 해제' : '오늘만 끄기';
            todayOffBtn.classList.toggle('hb-btn-soft-on', isTodayOff());
        }
        if(policyText){
            policyText.textContent = (cfg.mode === 'admin_operation' ? '관리자 공용 운영판 · ' : '') + '우선순위: ' + (settings.priority_label || '정각 시간표 우선') + ' · 정각 허용범위 ' + (settings.single_window_seconds || 90) + '초';
        }
        if(isTodayOff()){
            setState('오늘 꺼짐');
            setStatus('오늘은 자동재생을 쉬는 중입니다. 해제하면 다시 시간표대로 재생됩니다.');
        }else if(soundReady){
            setState('켜짐');
            setStatus(cfg.mode === 'admin_operation' ? '공통 운영표에 맞춰 이 관리자 기기에서만 재생됩니다.' : '사이트 전체 방송 시간표를 확인하고 있습니다.');
        }else{
            setState('대기');
            setStatus('처음 한 번은 음악 알림 켜기를 눌러주세요.');
        }
        updateDebugBadge();
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
            const sa = Number.parseInt(a.sort || 0, 10) || 0;
            const sb = Number.parseInt(b.sort || 0, 10) || 0;
            if(sa !== sb) return sa - sb;
            const aid = Number.parseInt(a.log_id != null ? a.log_id : String(a.id || '').replace(/\D+/g, ''), 10) || 0;
            const bid = Number.parseInt(b.log_id != null ? b.log_id : String(b.id || '').replace(/\D+/g, ''), 10) || 0;
            return aid - bid;
        });
    }

    function broadcastOverridesAuto(){
        if(!cfg.apiBroadcast || !broadcastState) return false;
        if(broadcastState.mode === 'stop') return true;
        if(broadcastState.mode !== 'manual') return false;
        const revision = Number(broadcastState.revision || 0);
        return !(revision && broadcastEndedRevision === revision);
    }

    function broadcastDesiredPosition(state, serverEpochMs){
        const base = Math.max(0, Number((state && state.seek_seconds) || 0));
        const started = Math.max(0, Number((state && state.started_epoch_ms) || 0));
        const serverNow = Math.max(0, Number(serverEpochMs || 0)) || (Date.now() + serverClockOffsetMs);
        if(!started) return base;
        return Math.max(0, base + Math.max(0, serverNow - started) / 1000);
    }

    async function applyBroadcastState(next, serverEpochMs){
        if(!cfg.apiBroadcast || !next) return false;
        const revision = Number(next.revision || 0);
        const knownRevision = broadcastState ? Number(broadcastState.revision || 0) : 0;
        // 방송 상태는 전용 polling뿐 아니라 시간표 API 응답에도 포함됩니다.
        // 어느 경로에서 오더라도 낮은 revision이 이미 적용된 최신 상태를 되감지 못하게 중앙에서 막습니다.
        if(knownRevision > 0 && revision > 0 && revision < knownRevision) return broadcastOverridesAuto();
        const prevMode = broadcastState ? broadcastState.mode : 'auto';
        const prevRevision = knownRevision;
        broadcastState = Object.assign({mode:'auto', revision:revision, started_epoch_ms:0, seek_seconds:0, item:null}, next || {});
        if(revision !== prevRevision){
            if(localBroadcastStoppedRevision && localBroadcastStoppedRevision !== revision) localBroadcastStoppedRevision = writeRevisionFlag('broadcast_stopped_revision', 0);
            if(broadcastEndedRevision && broadcastEndedRevision !== revision) broadcastEndedRevision = writeRevisionFlag('broadcast_ended_revision', 0);
        }

        // 관리자 로컬 미리듣기는 서버 전체송출 상태와 독립적입니다.
        // polling은 상태만 갱신하고 현재 로컬 미리듣기를 중간에 끊지 않습니다.
        if(localPreviewOverride) return true;

        if(broadcastState.mode === 'auto'){
            localBroadcastStoppedRevision = writeRevisionFlag('broadcast_stopped_revision', 0);
            broadcastEndedRevision = writeRevisionFlag('broadcast_ended_revision', 0);
            if(prevMode !== 'auto'){
                // 수동 전체송출 시작이 아직 await 중이어도 자동 편성 전환은 즉시 그 재생 세대를 취소해야 합니다.
                stopMedia(true);
                stopActiveBlock();
                currentBroadcastRevision = 0;
                clearSitewideResume();
                setStatus('사이트 전체 방송이 자동 편성으로 돌아왔습니다.');
                setTimeout(checkDue, 80);
            }
            return false;
        }

        if(broadcastState.mode === 'stop'){
            localBroadcastStoppedRevision = writeRevisionFlag('broadcast_stopped_revision', 0);
            broadcastEndedRevision = writeRevisionFlag('broadcast_ended_revision', 0);
            if(isMediaPlaying() || currentBroadcastRevision || prevMode !== 'stop') {
                stopMedia(true);
                stopActiveBlock();
                clearSitewideResume();
            }
            currentBroadcastRevision = revision;
            setState('전체 정지');
            setStatus('관리자가 사이트 전체 방송을 정지했습니다.');
            if(nowTitle) nowTitle.textContent = '하루BGM · 전체 정지';
            return true;
        }

        const item = broadcastState.item;
        if(broadcastState.mode !== 'manual' || !item) return false;
        if(localBroadcastStoppedRevision === revision){
            setState('내 기기 정지');
            setStatus('이 기기에서만 현재 전체 방송을 정지했습니다. 다음 전체 방송 명령부터 다시 따라갑니다.');
            return true;
        }
        if(broadcastEndedRevision === revision) return false;
        if(!canThisTabPlay()){
            if(isMediaPlaying() || currentBroadcastRevision){ stopMedia(true); stopActiveBlock(); currentBroadcastRevision = 0; }
            setState('다른 탭 재생 중');
            setStatus('같은 브라우저의 다른 탭에서 하루BGM을 재생 중입니다.');
            return true;
        }
        if(isTodayOff()) return true;
        if(!soundReady){
            setState('소리 허용 필요');
            setStatus('전체 방송 중입니다. BGM 켜기를 한 번 눌러 소리를 허용해주세요.');
            if(nowTitle) nowTitle.textContent = item.title || item.music_title || '사이트 전체 방송';
            return true;
        }

        const desired = broadcastDesiredPosition(broadcastState, serverEpochMs);
        const sameItem = currentBroadcastRevision === revision && currentItem && String(currentItem.music_id || currentItem.mf_id || '') === String(item.music_id || item.mf_id || '');
        if(sameItem && isMediaPlaying()) {
            const drift = Math.abs(currentMediaTime() - desired);
            if(drift > 4){
                try{
                    if(item.source === 'youtube' || item.youtube_id){ if(ytPlayer && ytReady) ytPlayer.seekTo(desired, true); }
                    else if(audio) audio.currentTime = desired;
                }catch(e){}
            }
            return true;
        }

        stopMedia(true);
        stopActiveBlock();
        const ok = await playItem(item, false, {broadcast:true, resumeAt:desired, suppressLog:true});
        if(ok){
            currentBroadcastRevision = revision;
            setState('전체 방송');
            setStatus('관리자 전체 송출 · 같은 시작 시각을 기준으로 재생 중입니다.');
        }
        return !!ok;
    }

    function beginLocalPreviewOverride(){
        if(!cfg.apiBroadcast) return;
        localPreviewOverride = true;
        // 전체송출 재생 세대와 관리자 로컬 미리듣기를 분리합니다.
        // 그렇지 않으면 미리듣기 종료가 전체송출 종료로 오인되거나 다음 polling이 미리듣기를 즉시 덮어쓸 수 있습니다.
        currentBroadcastRevision = 0;
    }

    function endLocalPreviewOverride(){
        if(!localPreviewOverride) return false;
        localPreviewOverride = false;
        if(cfg.apiBroadcast){
            setTimeout(function(){
                loadBroadcast().then(function(overridden){ if(!overridden && !broadcastOverridesAuto()) checkDue(); });
            }, 80);
        }
        return true;
    }

    function applyBroadcastStateOnce(next, serverEpochMs){
        if(!next) return Promise.resolve(false);
        const key = String(next.mode || 'auto') + ':' + String(Number(next.revision || 0));
        if(broadcastApplyPromise && broadcastApplyKey === key) return broadcastApplyPromise;
        broadcastApplyKey = key;
        const task = Promise.resolve().then(function(){ return applyBroadcastState(next, serverEpochMs); });
        broadcastApplyPromise = task.then(function(value){
            if(broadcastApplyPromise === wrapped){ broadcastApplyPromise = null; broadcastApplyKey = ''; }
            return value;
        }, function(err){
            if(broadcastApplyPromise === wrapped){ broadcastApplyPromise = null; broadcastApplyKey = ''; }
            throw err;
        });
        const wrapped = broadcastApplyPromise;
        return wrapped;
    }

    async function loadBroadcast(){
        if(!cfg.apiBroadcast) return false;
        const requestSeq = ++broadcastRequestSeq;
        try{
            const started = Date.now();
            const res = await fetch(cfg.apiBroadcast + '?_=' + Date.now(), {credentials:'same-origin', cache:'no-store'});
            const json = await res.json();
            if(!json.ok) return false;
            // 느린 이전 polling 응답이 최신 방송 상태를 되감지 못하게 합니다.
            if(requestSeq < broadcastAcceptedRequestSeq) return false;
            const nextRevision = Number(json.broadcast && json.broadcast.revision || 0);
            const currentRevision = Number(broadcastState && broadcastState.revision || 0);
            if(currentRevision > 0 && nextRevision > 0 && nextRevision < currentRevision) return false;
            broadcastAcceptedRequestSeq = requestSeq;
            syncServerClock(json.server_time || '', started, json.server_epoch_ms);
            return await applyBroadcastStateOnce(json.broadcast || null, json.server_epoch_ms);
        }catch(e){
            return false;
        }
    }

    function scheduleRetryLater(){
        if(scheduleRetryTimer) return;
        const delays = [5000, 15000, 30000];
        const delay = delays[Math.min(scheduleRetryStep, delays.length - 1)];
        scheduleRetryStep = Math.min(scheduleRetryStep + 1, delays.length - 1);
        scheduleRetryTimer = setTimeout(function(){
            scheduleRetryTimer = null;
            loadSchedule();
        }, delay);
    }

    async function bootstrapAfterScheduleReady(){
        if(scheduleBootstrapped) return;
        scheduleBootstrapped = true;
        if(cfg.apiBroadcast){
            const overridden = await loadBroadcast();
            if(overridden || broadcastOverridesAuto()) return;
        }
        const restored = await restoreSitewideResume();
        if(!restored) checkDue();
    }

    // 지금 재생 중인 곡의 mf_volume(기본 볼륨)이 관리자에서 방금 바뀌었다면,
    // 다음 시간표 갱신(auto_refresh_seconds 주기) 때 이 값으로 재생 중인 소리를
    // 부드럽게 맞춥니다. 이전에는 이미 재생을 시작한 곡은 그 곡이 끝나거나 페이지를
    // 새로고침해야만 새 볼륨이 반영되어, "볼륨을 바꿔도 바로 안 줄어든다"는 문제가 있었습니다.
    function findLatestVolumeForMusicId(musicId){
        if(!musicId) return null;
        const id = Number(musicId);
        for(let i=0;i<schedules.length;i++){
            const s = schedules[i];
            if(Number(s.music_id||0) === id) return Number(s.volume);
            if(Array.isArray(s.items)){
                for(let j=0;j<s.items.length;j++) if(Number(s.items[j].music_id||0) === id) return Number(s.items[j].volume);
            }
        }
        for(let i=0;i<blocks.length;i++){
            const b = blocks[i];
            if(Array.isArray(b.items)){
                for(let j=0;j<b.items.length;j++) if(Number(b.items[j].music_id||0) === id) return Number(b.items[j].volume);
            }
        }
        return null;
    }
    function syncPlayingVolumeWithLatestSchedule(){
        if(!currentItem || currentManual) return; // 미리듣기는 관리자가 직접 조절한 볼륨이므로 건드리지 않습니다.
        const musicId = currentItem.music_id || currentItem.id;
        const latest = findLatestVolumeForMusicId(musicId);
        if(latest === null || !Number.isFinite(latest)) return;
        const savedVol = getSavedVolume();
        const newFinalVol = Math.min(savedVol, Math.max(0, Math.min(100, latest)));
        const target = newFinalVol / 100;
        if(audio && !audio.paused && Math.abs(audio.volume - target) > 0.01){
            currentItem.volume = latest;
            fadeInAudioFrom(target, 0.6);
        } else if(ytPlayer && ytReady && isYouTubePlaying()){
            try{
                const ytCurrent = ytPlayer.getVolume();
                if(Math.abs(ytCurrent - newFinalVol) > 1){ currentItem.volume = latest; fadeInYouTubeFrom(newFinalVol, 0.6); }
            }catch(e){}
        }
    }

    async function loadSchedule(){
        if(!cfg.apiSchedule) return false;
        const requestSeq = ++scheduleRequestSeq;
        try{
            const requestStartedAt = Date.now();
            const res = await fetch(cfg.apiSchedule + '?_=' + Date.now(), {credentials:'same-origin', cache:'no-store'});
            if(!res.ok) throw new Error('http_'+res.status);
            const json = await res.json();
            if(!json.ok){ throw new Error(json.message || 'load_failed'); }
            if(requestSeq < scheduleAcceptedRequestSeq) return false;
            scheduleAcceptedRequestSeq = requestSeq;
            if(scheduleRetryTimer){ clearTimeout(scheduleRetryTimer); scheduleRetryTimer = null; }
            scheduleRetryStep = 0;
            syncServerClock(json.server_time, requestStartedAt, json.server_epoch_ms);
            settings = Object.assign(settings, json.settings || {});
            settings.single_window_seconds = Math.max(30, Math.min(600, parseInt(settings.single_window_seconds || 90, 10)));
            settings.fadeout_seconds = Math.max(0, Math.min(20, parseInt(settings.fadeout_seconds || 4, 10)));
            settings.auto_refresh_seconds = Math.max(15, Math.min(300, parseInt(settings.auto_refresh_seconds || 60, 10)));
            schedules = sortByPriority(Array.isArray(json.items) ? json.items : []);
            blocks = sortByPriority(Array.isArray(json.blocks) ? json.blocks : []);
            syncPlayingVolumeWithLatestSchedule();
            if(schedules.concat(blocks).some(function(x){
                if(x.source === 'youtube' || x.youtube_id) return true;
                if(Array.isArray(x.items)) return x.items.some(function(i){ return i.source === 'youtube' || i.youtube_id; });
                return false;
            })) loadYouTubeApi().catch(function(err){ if(nowDesc && !isMediaPlaying()) nowDesc.textContent = 'YouTube API 준비 실패 · 다음 재생 시 다시 시도합니다.'; });
            updateRefreshTimer();
            updateButtons();
            updateNext();
            updateListState();
            if(json.broadcast && cfg.apiBroadcast) await applyBroadcastStateOnce(json.broadcast, json.server_epoch_ms);
            bootstrapAfterScheduleReady().catch(function(){});
            return true;
        }catch(e){
            if(nowDesc) nowDesc.textContent = '시간표를 불러오지 못했습니다. 잠시 후 자동으로 다시 시도합니다.';
            scheduleRetryLater();
            return false;
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

    async function playFile(item, finalVol, startAt){
        if(!audio || !item.url) throw new Error('file_player_missing');
        mediaGeneration++;
        const playGeneration = mediaGeneration;
        audioActiveGeneration = 0;
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        pauseYouTube();
        showFilePlayer();
        audio.src = item.url;
        const targetVolume = Math.max(0, Math.min(1, finalVol / 100));
        // 최종 볼륨으로 곧장 시작하지 않고 0에서 살짝 올려, 재생 시작 순간의 팝/클릭
        // 노이즈("툭" 소리)를 없앱니다. 페이지 이동 후 이어재생처럼 곡 중간에서
        // 시작할 때 특히 두드러지던 현상입니다.
        audio.volume = 0;
        if(startAt && startAt > 0){
            if(audio.readyState < 1) await new Promise(function(resolve){ const done=function(){ audio.removeEventListener('loadedmetadata',done); resolve(); }; audio.addEventListener('loadedmetadata',done,{once:true}); setTimeout(done,1200); });
            // metadata 대기 중 다른 재생으로 교체된 세대는 여기서 즉시 중단합니다.
            // 이 오류를 currentTime 보정 예외와 같이 삼키면 오래된 호출이 새 src에 audio.play()를 다시 걸 수 있습니다.
            if(playGeneration !== mediaGeneration) throw new Error('media_replaced');
            try{
                if(Number.isFinite(audio.duration) && audio.duration > 0) startAt = Math.min(startAt, Math.max(0, audio.duration - .15));
                audio.currentTime = Math.max(0, Number(startAt || 0));
            }catch(e){}
        }
        try{
            await audio.play();
        }catch(e){
            if(playGeneration !== mediaGeneration){
                const replaced = new Error('media_replaced');
                replaced.hbGeneration = playGeneration;
                throw replaced;
            }
            // 브라우저가 던진 DOMException은 읽기 전용/비확장 객체일 수 있으므로
            // 원본 객체를 직접 수정하지 않고 세대 정보를 가진 일반 Error로 감쌉니다.
            const wrapped = new Error(e && e.message ? String(e.message) : 'audio_play_failed');
            wrapped.name = e && e.name ? String(e.name) : 'Error';
            wrapped.hbGeneration = playGeneration;
            wrapped.hbCause = e || null;
            throw wrapped;
        }
        if(playGeneration !== mediaGeneration){
            const replaced = new Error('media_replaced');
            replaced.hbGeneration = playGeneration;
            throw replaced;
        }
        audioActiveGeneration = playGeneration;
        fadeInAudioFrom(targetVolume, 0.35);
    }

    async function playYouTube(item, finalVol, startAt){
        if(!item.youtube_id) throw new Error('youtube_id_missing');
        mediaGeneration++;
        const playGeneration = mediaGeneration;
        ytExpectedGeneration = playGeneration;
        ytExpectedVideoId = String(item.youtube_id || '');
        ytActiveGeneration = 0;
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        if(audio) audio.pause();
        showYouTubePlayer();
        ytLastError = null;
        const p = await ensureYouTubePlayer();
        if(playGeneration !== mediaGeneration) throw new Error('media_replaced');
        const targetVolume = Math.max(0, Math.min(100, finalVol));
        // 파일 재생과 동일한 이유로, YouTube도 곡 중간에서 이어재생할 때 최종 볼륨으로
        // 곧장 시작하면 팝 노이즈가 들릴 수 있어 짧게 페이드인합니다.
        p.setVolume(0);
        if(startAt && startAt > 0) p.loadVideoById({videoId:item.youtube_id,startSeconds:Math.max(0,Number(startAt||0))});
        else p.loadVideoById(item.youtube_id);
        p.playVideo();
        await waitForYouTubePlayback(5500);
        if(playGeneration !== mediaGeneration) throw new Error('media_replaced');
        ytActiveGeneration = playGeneration;
        fadeInYouTubeFrom(targetVolume, 0.35);
    }
    function fadeInYouTubeFrom(targetVolume, seconds){
        seconds = Math.max(0.05, Number(seconds) || 0.35);
        if(fadeTimer){ clearInterval(fadeTimer); fadeTimer = null; }
        const steps = Math.max(1, Math.round(seconds * 20));
        const fadeGeneration = mediaGeneration;
        let done = 0;
        fadeTimer = setInterval(function(){
            if(fadeGeneration !== mediaGeneration || !ytPlayer || !ytReady){ clearInterval(fadeTimer); fadeTimer = null; return; }
            done++;
            const ratio = Math.min(1, done / steps);
            try{ ytPlayer.setVolume(Math.round(targetVolume * ratio)); }catch(e){}
            if(ratio >= 1){
                clearInterval(fadeTimer);
                fadeTimer = null;
            }
        }, Math.max(10, Math.round((seconds * 1000) / steps)));
    }
    function logPlayback(item, manual, ctx, status, message){
        if(!cfg.apiLog || !item) return;
        try{
            const block = ctx && ctx.block ? ctx.block : null;
            const isSequencePreview = !!(manual && cfg.mode === 'sequence_runner');
            let logId = item.id || '0';
            let logScope = item.scope || 'preview';
            if(block){
                logId = block.log_id || block.id || '0';
                if(block.kind === 'single_set') logScope = block.scope || 'global';
                else if(block.kind === 'preview_set') logScope = block.previewBlock ? 'preview_block' : 'preview';
                else logScope = (block.scope || 'global') + '_block';
            }
            if(isSequencePreview){
                logId = cfg.sequenceId || (block && block.log_id) || '0';
                logScope = 'sequence';
            }
            const body = new URLSearchParams();
            body.set('sc_id', logId);
            body.set('mf_id', item.music_id || item.mf_id || '0');
            body.set('scope', logScope);
            body.set('action', manual ? (isSequencePreview ? 'manual' : 'preview') : 'auto');
            body.set('status', status || 'success');
            body.set('message', String(message || '').slice(0, 240));
            if(cfg.csrfToken) body.set('hb_token', String(cfg.csrfToken));
            // 표준 token(그누보드 get_token())은 하루BGM API가 검사하지 않는 값이라 더 이상 보내지 않습니다.
            // 일부 서버 환경에서 그누보드 관리자 공통 레이어의 1회용 토큰과 충돌해
            // 저장 요청이 부당하게 막히는 원인이 됐습니다.
            fetch(cfg.apiLog, {method:'POST', credentials:'same-origin', body}).catch(function(){});
        }catch(e){}
    }


    async function playItem(item, manual, ctx){
        if(!item) return;
        if(isTodayOff() && !manual) return;
        const isBlock = ctx && ctx.block;
        const isBroadcast = !!(ctx && ctx.broadcast);
        const savedVol = getSavedVolume();
        const itemVol = typeof item.volume === 'number' ? item.volume : parseInt(item.volume || savedVol,10);
        const finalVol = manual ? savedVol : Math.min(savedVol, itemVol);
        setNowTexts(item, manual, ctx);
        try{
            const resumeAt = ctx && ctx.resumeAt ? Number(ctx.resumeAt) : 0;
            if(item.source === 'youtube' || item.youtube_id) await playYouTube(item, finalVol, resumeAt);
            else await playFile(item, finalVol, resumeAt);
            if(!manual && !isBlock && !isBroadcast){
                clearRetry('single', item);
                const suppress = ctx && Array.isArray(ctx.suppressSingles) ? ctx.suppressSingles : [item];
                suppress.forEach(markPlayed);
            }else if(!manual && isBlock && ctx.block && ctx.block.kind === 'single_set' && !ctx.block._playedMarked){
                const suppress = Array.isArray(ctx.block._suppressSingles) ? ctx.block._suppressSingles : [];
                suppress.forEach(markPlayed);
                ctx.block._playedMarked = true;
            }
            if(!manual && isBlock && ctx.block) {
                clearRetry('block', ctx.block);
                if(ctx.block.mode === 'random' && !ctx.block.repeat && !isUntimedSet(ctx.block)) {
                    ctx.block._playedIndexes = ctx.block._playedIndexes || {};
                    if(activeBlockIndex >= 0) ctx.block._playedIndexes[activeBlockIndex] = true;
                }
            }
            const singleSetAuto = isBlock && ctx.block && ctx.block.kind === 'single_set';
            currentAuto = manual ? null : {
                priority: isBroadcast ? -100 : normalizePriority(isBlock ? ctx.block.priority : item.priority),
                kind: isBroadcast ? 'broadcast' : (singleSetAuto ? 'single' : (isBlock ? 'block' : 'single')),
                id: isBroadcast ? Number(broadcastState.revision || 0) : (singleSetAuto ? (ctx.block.log_id || String(ctx.block.id || '').replace(/^single_set_/, '')) : (isBlock ? ctx.block.id : item.id)),
                startedAt: Date.now(),
                serviceDate: serviceDateOf(isBlock ? ctx.block : item)
            };
            currentItem = item;
            currentManual = !!manual;
            setState(isBroadcast ? '전체 방송' : (manual ? '미리듣기' : (isBlock ? '시간대 재생' : '재생 중')));
            setStatus(isBroadcast ? '관리자 전체 송출을 재생 중입니다.' : (manual ? '미리듣기는 내 브라우저에서만 재생됩니다.' : (isBlock ? (ctx.block.kind === 'range' ? '설정된 시간 안에서만 음악을 재생합니다.' : '시간대 안의 음악을 이어서 재생합니다.') : '방금 시간표 음악을 재생했습니다.')));
            if(!(ctx && ctx.suppressLog)) logPlayback(item, manual, ctx, 'success', ctx && ctx.resume ? '페이지 이동 후 이어재생' : '재생 시작');
            updateListState();
        }catch(e){
            if(e && typeof e.hbGeneration !== 'undefined' && Number(e.hbGeneration) !== Number(mediaGeneration)) return false;
            if(e && e.message === 'media_replaced') return false;
            const errorName = e && e.name ? String(e.name) : '';

            // 자동재생 정책 거부는 곡 자체의 실패가 아닙니다.
            // 현재 블록을 실패 인덱스로 오염시키지 않고 사용자 제스처 후 다시 선택되게 합니다.
            if(errorName === 'NotAllowedError'){
                if(!(ctx && ctx.suppressLog)) logPlayback(item, manual, ctx, 'fail', 'autoplay_not_allowed');
                if(isBlock && activeBlock) stopActiveBlock();
                else if(!manual && !isBroadcast) deferRetry('single', item, 1500);
                setState('소리 허용 필요');
                setStatus(isBroadcast ? '전체 방송 중입니다. BGM 켜기를 눌러 소리를 허용해주세요.' : '브라우저가 자동재생을 막았습니다. BGM 켜기를 누르면 다시 시도합니다.');
                if(nowDesc) nowDesc.textContent = '브라우저의 소리 재생 권한이 필요합니다.';
                if(manual && !isBlock) endLocalPreviewOverride();
                return false;
            }

            // 같은 세대에서의 AbortError는 일시적인 재생 전환 중단으로 취급합니다.
            // 곡을 영구 실패 처리하지 않고 짧게 유예한 뒤 현재 편성을 다시 판정합니다.
            if(errorName === 'AbortError'){
                if(!(ctx && ctx.suppressLog)) logPlayback(item, manual, ctx, 'fail', 'play_interrupted');
                if(isBlock && activeBlock){
                    const interruptedBlock = activeBlock;
                    deferRetry('block', interruptedBlock, 1500);
                    stopActiveBlock();
                }else if(!manual && !isBroadcast){
                    deferRetry('single', item, 1500);
                }
                setStatus('재생 전환이 중단되어 잠시 후 현재 편성을 다시 확인합니다.');
                if(nowDesc) nowDesc.textContent = '재생 전환 중단 · 자동 재시도 예정';
                if(!manual && !isBroadcast) setTimeout(checkDue, 1800);
                if(manual && !isBlock) endLocalPreviewOverride();
                return false;
            }

            if(!(ctx && ctx.suppressLog)) logPlayback(item, manual, ctx, 'fail', e && e.message ? e.message : 'play_failed');
            if(isBroadcast){
                setState('소리 허용 필요');
                setStatus('전체 방송을 시작하지 못했습니다. BGM 켜기 후 다시 시도해주세요.');
            }else if(isBlock && activeBlock){
                deferRetry('block', activeBlock, 8000);
                if(activeBlock.kind === 'single_set' && Array.isArray(activeBlock._suppressSingles)){
                    activeBlock._suppressSingles.forEach(function(x){ deferRetry('single', x, 5000); });
                }
                activeBlock._failedIndexes = activeBlock._failedIndexes || {};
                activeBlock._failedIndexes[activeBlockIndex] = true;
                setStatus('재생할 수 없는 곡을 건너뛰고 다음 곡을 확인합니다.');
                setTimeout(function(){ if(activeBlock) playNextBlockTrack(); }, 180);
            }else{
                if(!manual) deferRetry('single', item, 5000);
                setState('소리 허용 필요');
                setStatus('브라우저 자동재생 또는 YouTube 임베드 제한으로 시작하지 못했습니다. 소리 켜기 후 다시 테스트해주세요.');
            }
            if(nowDesc) nowDesc.textContent = '재생을 시작하지 못했습니다. 소리 허용/YouTube 임베드 가능 여부를 확인해주세요.';
            if(manual && !isBlock) endLocalPreviewOverride();
            return false;
        }
        return true;
    }

    function startBlock(block){
        if(!block || !Array.isArray(block.items) || !block.items.length) return;
        if(!isUntimedSet(block) && !block.repeat && isBlockDone(block)) return;
        activeBlock = block;
        activeBlock._failedIndexes = {};
        activeBlock._playedIndexes = {};
        activeBlockIndex = block.mode === 'random' ? chooseBlockIndex(block, -1) : 0;
        updateDebugBadge();
        return playItem(block.items[activeBlockIndex], false, {block:block});
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
            service_date: item.service_date || '',
            items: items,
            _suppressSingles: [item]
        };
        return startBlock(block);
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
            const allFailed = activeBlock._failedIndexes && Object.keys(activeBlock._failedIndexes).length >= activeBlock.items.length;
            if(!isUntimedSet(activeBlock) && !allFailed) markBlockDone(activeBlock);
            const endedKind = activeBlock.kind;
            stopActiveBlock(allFailed ? '재생 가능한 곡을 찾지 못했습니다. 잠시 뒤 다시 시도합니다.' : (isUntimedSet(activeBlock) ? (activeBlock.kind === 'preview_set' ? '미리듣기 세트의 모든 음악을 재생했습니다.' : '정각 세트의 모든 음악을 재생했습니다.') : '시간대 묶음의 모든 곡을 재생했습니다.'));
            currentItem = null;
            currentManual = false;
            setState(soundReady ? '켜짐' : '대기');
            if(endedKind === 'preview_set') {
                if(!endLocalPreviewOverride()) setTimeout(checkDue, 150);
            }
            return;
        }
        activeBlockIndex = next;
        playItem(activeBlock.items[activeBlockIndex], !!activeBlock.manual, {block:activeBlock});
    }

    function handleMediaError(source){
        source = source === 'youtube' ? 'youtube' : 'file';
        if(!currentItem) return;
        if(source === 'file'){
            if(!audio || currentItem.source === 'youtube' || currentItem.youtube_id || audioActiveGeneration !== mediaGeneration) return;
            // src 교체/정지 과정의 MEDIA_ERR_ABORTED는 의도된 중단이므로 재생 실패로 처리하지 않습니다.
            if(audio.error && Number(audio.error.code || 0) === 1) return;
        }else{
            if(!(currentItem.source === 'youtube' || currentItem.youtube_id) || ytActiveGeneration !== mediaGeneration) return;
        }
        const failedGeneration = mediaGeneration;
        const sourceLabel = source === 'youtube' ? 'YouTube' : '파일';
        const failedItem = currentItem;
        const failedManual = currentManual;
        const failedBlock = activeBlock;
        if(currentBroadcastRevision && broadcastState && broadcastState.mode === 'manual'){
            currentAuto = null; currentItem = null; currentManual = false;
            clearSitewideResume();
            setState('재생 오류');
            setStatus('전체 방송 '+sourceLabel+' 재생 오류가 발생했습니다. 다음 동기화에서 다시 시도합니다.');
            return;
        }
        if(failedBlock){
            logPlayback(failedItem, failedManual, {block:failedBlock}, 'fail', sourceLabel+' 재생 중 오류');
            failedBlock._failedIndexes = failedBlock._failedIndexes || {};
            if(activeBlockIndex >= 0) failedBlock._failedIndexes[activeBlockIndex] = true;
            deferRetry('block', failedBlock, 8000);
            setStatus(sourceLabel+' 재생 중 오류가 발생해 다음 곡을 확인합니다.');
            setTimeout(function(){
                if(mediaGeneration === failedGeneration && activeBlock === failedBlock) playNextBlockTrack();
            }, 120);
            return;
        }
        logPlayback(failedItem, failedManual, null, 'fail', sourceLabel+' 재생 중 오류');
        if(!failedManual) deferRetry('single', failedItem, 5000);
        currentAuto = null; currentItem = null; currentManual = false;
        clearSitewideResume();
        setState('재생 오류');
        setStatus(currentBroadcastRevision ? '전체 방송 '+sourceLabel+' 재생 오류가 발생했습니다. 다음 동기화에서 다시 시도합니다.' : sourceLabel+' 재생 중 오류가 발생했습니다. 잠시 뒤 다시 시도합니다.');
        if(failedManual && !endLocalPreviewOverride()) setTimeout(checkDue, 150);
    }

    function handleMediaEnded(){
        if(currentBroadcastRevision && broadcastState && broadcastState.mode === 'manual'){
            broadcastEndedRevision = writeRevisionFlag('broadcast_ended_revision', currentBroadcastRevision);
            currentBroadcastRevision = 0;
            currentAuto = null; currentItem = null; currentManual = false;
            clearSitewideResume();
            setState('전체 방송 종료');
            setStatus('현재 전체 송출 곡 재생이 끝났습니다. 자동 편성을 다시 확인합니다.');
            setTimeout(checkDue, 150);
            return;
        }
        if(activeBlock){ playNextBlockTrack(); return; }
        const wasManual = currentManual;
        currentAuto = null; currentItem = null; currentManual = false; clearSitewideResume();
        if(wasManual && !endLocalPreviewOverride()) setTimeout(checkDue, 150);
    }

    function singleElapsed(item){
        const ns = nowSec();
        const target = secOf(item.time);
        const serviceDate = serviceDateOf(item);
        const today = dateKey();
        const yesterday = dateKey(addDays(clockNow(), -1));
        if(serviceDate === yesterday) return ns + 86400 - target;
        if(serviceDate !== today) return Number.POSITIVE_INFINITY;
        return ns - target;
    }

    function dueSingles(){
        const win = settings.single_window_seconds || 90;
        const out = [];
        schedules.forEach(function(item){
            const diff = singleElapsed(item);
            if(!Number.isFinite(diff) || diff < 0 || diff > win) return;
            if(isPlayed(item) || singleIsInFlight(item) || !retryReady('single', item)) return;
            item.diff = diff;
            out.push(item);
        });
        return sortByPriority(out);
    }

    function runningBlocks(){
        return sortByPriority(blocks.filter(function(block){
            if(!blockContainsNow(block)) return false;
            if(!block.repeat && isBlockDone(block)) return false;
            if(!retryReady('block', block)) return false;
            return Array.isArray(block.items) && block.items.length > 0;
        }));
    }

    function checkDue(){
        if(!canThisTabPlay()) return;
        if(broadcastOverridesAuto()) return;
        if(!soundReady || isTodayOff()) return;
        if(currentManual && isMediaPlaying()) return;

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
        // 현재 재생 중인 블록만 고정해서 보지 않고, 지금 시각에 활성인 모든 블록을 다시 비교합니다.
        // 그래야 뒤늦게 시작한 더 높은 우선순위 방송이 기존 낮은 우선순위를 정상적으로 교체할 수 있습니다.
        const rBlocks = runningBlocks();
        const blockCandidate = rBlocks.length ? rBlocks[0] : null;
        const singleCandidate = singles.length ? singles[0] : null;
        if(!blockCandidate && !singleCandidate) return;

        const candidates = [];
        if(blockCandidate) candidates.push(Object.assign({candidateType:'block'}, blockCandidate));
        if(singleCandidate) candidates.push(Object.assign({candidateType:'single'}, singleCandidate));
        sortByPriority(candidates);
        const chosen = candidates[0];
        if(isManualStoppedCandidate(chosen)) return;
        if(readManualStop()) clearManualStop();

        // 같은 블록을 API 새로고침 뒤 새 객체로 받아도 재시작하지 않습니다.
        if(chosen.candidateType === 'block' && activeBlock && !activeBlock._ending && String(activeBlock.id) === String(chosen.id) && activeBlock.kind === chosen.kind) return;

        if(isMediaPlaying() && currentAuto){
            if(normalizePriority(chosen.priority) >= normalizePriority(currentAuto.priority)) return;
            fadeOutAndStop(0);
            if(activeBlock) stopActiveBlock();
        }

        if(chosen.candidateType === 'single'){
            // 실제 선택된 schedule 한 건만 성공 처리하며, 첫 play Promise가 끝날 때까지 같은 schedule의 재진입을 막습니다.
            runSingleCandidate(singleCandidate).catch(function(){});
            return;
        }

        if(blockCandidate.repeat) clearBlockDone(blockCandidate);
        startBlock(blockCandidate);
    }

    function updateNext(){
        const d = clockNow();
        if(clockEl) clockEl.textContent = hms(d);
        if(isTodayOff()){ if(countdownEl) countdownEl.textContent = '오늘 꺼짐'; return; }
        if(activeBlock && (isUntimedSet(activeBlock) || blockContainsNow(activeBlock))){ if(countdownEl) countdownEl.textContent = activeBlock.kind === 'preview_set' ? '미리듣기 세트 재생 중' : (activeBlock.kind === 'single_set' ? '정각 세트 재생 중' : '시간대 재생 중'); return; }
        const candidates = [];
        schedules.forEach(function(item){ if(!isPlayed(item)) candidates.push({type:'single', time:item.time, service_date:item.service_date, priority:item.priority}); });
        blocks.forEach(function(block){
            if(!Array.isArray(block.items) || !block.items.length) return;
            if(blockContainsNow(block) && (block.repeat || !isBlockDone(block))){ candidates.push({type:'block', time:null, now:true, priority:block.priority}); }
            else if(block.repeat || !isBlockDone(block)){ candidates.push({type:'block', time:block.start, service_date:block.service_date, priority:block.priority}); }
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
        const now = clockNow();
        const nowMs = now.getTime();
        const ns = nowSec();
        const currentDate = dateKey(now);
        todayList.querySelectorAll('.hb-schedule-item').forEach(function(el){
            const isBlockEl = el.classList.contains('hb-block-schedule');
            el.classList.remove('is-past','is-next','is-now','is-active-block');
            if(isBlockEl){
                const blockId = el.getAttribute('data-block-id');
                const blockKind = el.getAttribute('data-block-kind');
                const liveBlock = blockId ? blocks.find(function(b){ return String(b.id) === String(blockId) && (!blockKind || b.kind === blockKind); }) : null;
                if(liveBlock && blockContainsNow(liveBlock)){ el.classList.add('is-active-block'); return; }
                const st = secOf(el.getAttribute('data-start') || '00:00');
                const en = secOf(el.getAttribute('data-end') || '00:00');
                let inRange = false;
                if(!liveBlock){
                    const serviceDate = el.getAttribute('data-service-date') || currentDate;
                    const startMs = wallClockTargetMs(serviceDate, el.getAttribute('data-start') || '00:00');
                    let endMs = wallClockTargetMs(serviceDate, el.getAttribute('data-end') || '00:00');
                    if(Number.isFinite(startMs) && Number.isFinite(endMs)){
                        if(en < st) endMs += 86400000;
                        inRange = nowMs >= startMs && nowMs < endMs;
                        if(!inRange && nowMs >= endMs) el.classList.add('is-past');
                    }else{
                        if(en > st) inRange = ns >= st && ns < en;
                        else if(en < st) inRange = ns >= st || ns < en;
                        if(!inRange && en > st && ns > en) el.classList.add('is-past');
                    }
                }
                if(inRange) el.classList.add('is-active-block');
                return;
            }
            const itemTime = el.getAttribute('data-time') || '00:00';
            const serviceDate = el.getAttribute('data-service-date') || currentDate;
            const targetMs = wallClockTargetMs(serviceDate, itemTime);
            if(Number.isFinite(targetMs)){
                const elapsed = Math.floor((nowMs - targetMs) / 1000);
                if(elapsed >= -60 && elapsed <= Math.max(60, settings.single_window_seconds || 90)) el.classList.add('is-now');
                else if(elapsed > 0) el.classList.add('is-past');
                return;
            }
            const target = secOf(itemTime);
            if(Math.abs(ns - target) <= 60) el.classList.add('is-now');
            else if(ns > target) el.classList.add('is-past');
        });
        let nextEl = null;
        let remain = Number.POSITIVE_INFINITY;
        todayList.querySelectorAll('.hb-schedule-item').forEach(function(el){
            if(el.classList.contains('is-active-block') || el.classList.contains('is-past') || el.classList.contains('is-now')) return;
            const t = el.classList.contains('hb-block-schedule') ? (el.getAttribute('data-start') || '00:00') : (el.getAttribute('data-time') || '00:00');
            const serviceDate = el.getAttribute('data-service-date') || currentDate;
            const targetMs = wallClockTargetMs(serviceDate, t);
            const diff = Number.isFinite(targetMs) ? Math.max(0, Math.ceil((targetMs - nowMs)/1000)) : ((secOf(t) - ns + 86400) % 86400);
            if(diff < remain){ remain = diff; nextEl = el; }
        });
        if(nextEl) nextEl.classList.add('is-next');
    }

    if(enableBtn){
        enableBtn.addEventListener('click', async function(){
            if(isSitewideMode && !canThisTabPlay()){
                soundReady = true;
                storageSet(storageKey('sound_ready'),'1');
                updateButtons();
                setState('다른 탭 재생 중');
                setStatus('같은 브라우저의 다른 탭에서 하루BGM을 재생 중입니다. 해당 탭을 닫으면 자동으로 이어받습니다.');
                return;
            }
            soundReady = true;
            storageSet(storageKey('sound_ready'),'1');
            clearManualStop();
            localBroadcastStoppedRevision = writeRevisionFlag('broadcast_stopped_revision', 0);
            broadcastEndedRevision = writeRevisionFlag('broadcast_ended_revision', 0);
            await unlockAudio();
            ensureYouTubePlayer().catch(function(){});
            updateButtons();
            setTimeout(checkDue, 200);
        });
    }
    if(todayOffBtn){
        todayOffBtn.addEventListener('click', function(){
            setTodayOff(!isTodayOff());
            if(isTodayOff()){ localPreviewOverride = false; stopMedia(true); stopActiveBlock(); clearSitewideResume(); }
            updateNext();
        });
    }
    if(volumeToggleBtn && volumeRow){
        volumeToggleBtn.addEventListener('click', function(){
            const willShow = volumeRow.hasAttribute('hidden');
            if(willShow) volumeRow.removeAttribute('hidden');
            else volumeRow.setAttribute('hidden', '');
            volumeToggleBtn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
            volumeToggleBtn.classList.toggle('is-active', willShow);
            // 볼륨 줄이 펼쳐지고 접히면서 재생바 실제 높이가 바뀌므로, 하단 스페이서를
            // 다시 계산해 스크롤 맨 아래 빈 공간이 어긋나지 않게 맞춥니다.
            if(typeof updateSitewideSpacer === 'function') updateSitewideSpacer();
        });
    }
    if(stopBtn){
        stopBtn.addEventListener('click', function(){
            const stoppedAuto = currentAuto ? {kind:currentAuto.kind,id:currentAuto.id,serviceDate:currentAuto.serviceDate || serviceDateOf(currentItem)} : null;
            setManualStop(stoppedAuto);
            if(broadcastState && broadcastState.mode === 'manual') localBroadcastStoppedRevision = writeRevisionFlag('broadcast_stopped_revision', Number(broadcastState.revision || 0));
            localPreviewOverride = false;
            stopMedia(true);
            stopActiveBlock();
            clearSitewideResume();
            setState(soundReady ? '켜짐' : '대기');
            setStatus(stoppedAuto ? '현재 방송을 정지했습니다. 다음 편성부터 자동재생됩니다.' : '재생을 정지했습니다.');
        });
    }
    if(volume){
        volume.addEventListener('input', function(){
            storageSet(storageKey('volume'), volume.value);
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

    function startPreviewSet(title, items, resumeAt){
        items = Array.isArray(items) ? items : [];
        items = items.filter(function(item){ return item && (item.url || item.youtube_id); });
        if(!items.length) return;
        stopMedia(true);
        stopActiveBlock();
        clearSitewideResume();
        beginLocalPreviewOverride();
        const previewCtx = (resumeAt && resumeAt > 0) ? {resume:true, resumeAt:resumeAt, suppressLog:true} : undefined;
        if(items.length === 1 && !items[0].preview_block && cfg.mode !== 'sequence_runner'){
            playItem(items[0], true, previewCtx);
            return;
        }
        activeBlock = {
            kind: 'preview_set',
            manual: true,
            id: 'preview_' + Date.now(),
            log_id: Number((items[0] && items[0].id) || 0),
            scope: (items[0] && items[0].scope) || 'preview',
            previewBlock: !!(items[0] && items[0].preview_block),
            priority: 0,
            title: title || '미리듣기 세트',
            start: null,
            end: null,
            mode: 'sequence',
            repeat: 0,
            items: items
        };
        activeBlockIndex = 0;
        playItem(activeBlock.items[activeBlockIndex], true, Object.assign({block:activeBlock}, previewCtx || {}));
    }

    // "지금 실제 사이트에서 재생 중인 곡"을 조회 전용으로 확인합니다.
    // loadBroadcast()와 달리 이 결과로 오디오를 자동 재생시키지 않고, 값만 돌려줍니다.
    // today.php처럼 순수 테스트 목적 화면에서, 미리듣기가 실제 방송 위치와 다르게
    // 처음부터 재생되지 않도록 미리듣기 시작 직전에 이 값을 확인하는 용도입니다.
    async function fetchLiveBroadcastStatusOnce(){
        if(!cfg.apiBroadcastStatus) return null;
        try{
            const res = await fetch(cfg.apiBroadcastStatus + '?_=' + Date.now(), {credentials:'same-origin', cache:'no-store'});
            const json = await res.json();
            if(!json || !json.ok || !json.broadcast) return null;
            if(json.server_time) syncServerClock(String(json.server_time), Date.now(), json.server_epoch_ms);
            return json.broadcast;
        }catch(e){ return null; }
    }

    // 클릭한 미리듣기 항목이 지금 실제로 방송 중인 그 곡이면, 처음부터가 아니라
    // 서버가 실제로 재생 중인 시점(경과초)에 맞춰 시작할 위치를 계산합니다.
    // 자동 편성이든(mode:auto) 관리자가 수동 전체송출한 것이든(mode:manual) 모두 대상입니다.
    // serverClockOffsetMs로 서버-클라이언트 시간 오프셋을 이미 보정하므로, operation.php처럼
    // 폴링으로 항상 최신 상태인 broadcastState를 넘겨도, today.php처럼 클릭 시점에 한 번
    // 새로 조회한 상태를 넘겨도 동일하게 정확합니다.
    function computeLiveResumeSeconds(broadcast, musicId){
        if(!broadcast || !musicId) return 0;
        if(broadcast.mode !== 'auto' && broadcast.mode !== 'manual') return 0;
        if(!broadcast.item || Number(broadcast.item.music_id || 0) !== Number(musicId)) return 0;
        let seconds = Math.max(0, Number(broadcast.seek_seconds || 0));
        if (broadcast.started_epoch_ms) {
            const nowEpochMs = Date.now() + serverClockOffsetMs;
            seconds += Math.max(0, (nowEpochMs - Number(broadcast.started_epoch_ms)) / 1000);
        }
        return seconds;
    }

    const previewButtons = Array.prototype.slice.call(document.querySelectorAll('.hb-mini-play'));
    const pageHasYouTubePreview = previewButtons.some(function(btn){
        return parsePreviewItems(btn).some(function(item){ return item && (item.youtube_id || item.source === 'youtube'); });
    });
    if(pageHasYouTubePreview) ensureYouTubePlayer().catch(function(){});
    previewButtons.forEach(function(btn){
        btn.addEventListener('click', async function(){
            if(btn.dataset.confirm === '1' && !confirm('이 항목을 지금 재생할까요?')) return;
            const previewProbe = parsePreviewItems(btn);
            const isLiveButton = btn.dataset.live === '1';
            await unlockAudio();
            if(previewProbe.some(function(item){ return item && (item.youtube_id || item.source === 'youtube'); })){
                try{ await ensureYouTubePlayer(); }catch(e){}
            }
            (root.querySelectorAll ? root.querySelectorAll('.hb-manual-current') : []).forEach(function(x){ x.classList.remove('hb-manual-current'); });
            const row = btn.closest('.hb-schedule-item, .hb-sequence-step');
            if(row) row.classList.add('hb-manual-current');
            soundReady = true;
            storageSet(storageKey('sound_ready'),'1');
            updateButtons();
            // 클릭한 순간의 실제 방송 상태를 확인해, 지금 이 항목이 실제로 사이트에서
            // 재생 중이면 처음부터가 아니라 그 실시간 위치에서 미리듣기를 시작합니다.
            // operation.php처럼 broadcastState가 이미 폴링으로 최신 상태를 유지하는
            // 화면에서는 그 값을 그대로 쓰고, today.php처럼 그렇지 않은 화면은
            // 클릭 시점에 한 번 새로 조회합니다.
            let resumeAt = 0;
            let liveBroadcast = null;
            if(isLiveButton || previewProbe.length === 1){
                liveBroadcast = cfg.apiBroadcastStatus ? await fetchLiveBroadcastStatusOnce() : null;
                if(!liveBroadcast && cfg.apiBroadcast) liveBroadcast = broadcastState;
            }
            if(previewProbe.length === 1){
                resumeAt = computeLiveResumeSeconds(liveBroadcast, previewProbe[0] && previewProbe[0].music_id);
            } else if(isLiveButton && liveBroadcast && liveBroadcast.item){
                // 세트(여러 곡 묶음) 안의 항목 중 하나가 지금 실제 방송 중인 그 곡이면,
                // 그 곡부터 실시간 위치에서 이어서 미리듣기를 시작합니다.
                const liveMusicId = Number(liveBroadcast.item.music_id || 0);
                const liveIdx = previewProbe.findIndex(function(it){ return it && Number(it.music_id || 0) === liveMusicId; });
                if(liveIdx >= 0){
                    resumeAt = computeLiveResumeSeconds(liveBroadcast, liveMusicId);
                    if(resumeAt > 0){
                        // 세트 재생은 첫 곡부터 순서대로 진행되므로, 실제 방송 중인 그 곡이
                        // 세트 안에서 맨 앞에 오도록 순서를 맞춰 그 곡부터 바로 시작합니다.
                        const reordered = previewProbe.slice(liveIdx).concat(previewProbe.slice(0, liveIdx));
                        startPreviewSet(btn.dataset.title || '미리듣기', reordered, resumeAt);
                        return;
                    }
                }
            }
            if(isLiveButton && resumeAt <= 0){
                setStatus('지금은 이 항목이 실제로 방송 중이지 않아 처음부터 재생합니다.');
            }
            startPreviewSet(btn.dataset.title || '미리듣기', previewProbe, resumeAt);
        });
    });
    if(audio){
        audio.addEventListener('ended', function(){
            // src 교체 직전에 큐에 들어온 오래된 ended 이벤트가 새 곡을 넘기지 않게 재생 세대와 실제 ended 상태를 함께 확인합니다.
            if(audioActiveGeneration !== mediaGeneration || !audio.ended) return;
            handleMediaEnded();
        });
        audio.addEventListener('error', function(){ handleMediaError('file'); });
    }

    if(cfg.serverTime) syncServerClock(String(cfg.serverTime), Date.now(), cfg.serverEpochMs);

    if(isSitewideMode){
        updateSitewideSpacer();
        updateSitewideCollisionOffset();
        window.addEventListener('resize', updateSitewideSpacer);
        window.addEventListener('resize', updateSitewideCollisionOffset);
        window.addEventListener('load', updateSitewideCollisionOffset);
        window.addEventListener('load', updateSitewideSpacer);
        // 웹폰트가 늦게 적용되면 재생바 텍스트 줄바꿈이 바뀌어 실제 높이가 달라질 수 있어,
        // 폰트 로딩이 끝나는 시점에도 한 번 더 실측해 스페이서 높이를 맞춥니다.
        if(document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function'){
            document.fonts.ready.then(updateSitewideSpacer).catch(function(){});
        }
        // 초기 레이아웃이 한 프레임 늦게 확정되는 환경을 위한 안전망으로, 아주 짧은 지연 후
        // 한 번 더 재계산합니다. ResizeObserver가 이후의 모든 변화는 계속 따라갑니다.
        setTimeout(updateSitewideSpacer, 300);
        setInterval(updateSitewideCollisionOffset, 5000);
        if(typeof ResizeObserver !== 'undefined' && root){
            try{ const spacerObserver = new ResizeObserver(updateSitewideSpacer); spacerObserver.observe(root); }catch(e){}
        }
        claimPlaybackLeader(false);
        playbackLeaderHeartbeat = setInterval(function(){
            if(isPlaybackLeader()) claimPlaybackLeader(false);
            else if(!playbackLeaderState() || playbackLeaderState().expires <= Date.now()) claimPlaybackLeader(false);
        }, 2000);
        window.addEventListener('storage', function(e){
            if(!e || !e.key) return;
            if(e.key === playbackLeaderKey){
                if(!isPlaybackLeader() && isMediaPlaying()){
                    stopMedia(true); stopActiveBlock(); currentBroadcastRevision = 0;
                    setState('다른 탭 재생 중');
                }else if(isPlaybackLeader()) setTimeout(checkDue, 80);
                return;
            }
            if(e.key === todayOffKey() && e.newValue === '1'){
                stopMedia(true); stopActiveBlock(); clearSitewideResume();
                return;
            }
            if(e.key === manualStopKey() && e.newValue){
                stopMedia(true); stopActiveBlock(); clearSitewideResume();
                return;
            }
            if(e.key === storageKey('broadcast_stopped_revision') && e.newValue){
                localBroadcastStoppedRevision = parseInt(e.newValue || '0', 10) || 0;
                stopMedia(true); stopActiveBlock(); clearSitewideResume();
                return;
            }
            if(e.key === storageKey('broadcast_ended_revision') && e.newValue){
                broadcastEndedRevision = parseInt(e.newValue || '0', 10) || 0;
                stopMedia(true); stopActiveBlock(); clearSitewideResume();
                return;
            }
            if(e.key === storageKey('sound_ready')) soundReady = e.newValue === '1';
        });
        window.addEventListener('pagehide', releasePlaybackLeader);
    }

    cleanupOldDailyStorage(14);
    applySavedVolume();
    updateButtons();
    loadSchedule();
    if(cfg.apiBroadcast){
        broadcastPollTimer = setInterval(loadBroadcast, Math.max(1500, parseInt(cfg.broadcastPollMs || 2000, 10)));
    }
    setInterval(function(){
        const currentDate = dateKey();
        if(currentDate !== lastScheduleDate){
            lastScheduleDate = currentDate;
            loadSchedule().then(function(){ checkDue(); });
            return;
        }
        updateNext();
        updateListState();
        checkDue();
    }, 1000);
    if(sitewideResumeEnabled()){
        window.addEventListener('pagehide', saveSitewideResume);
        window.addEventListener('beforeunload', saveSitewideResume);
        setInterval(saveSitewideResume, 2000);
    }
    document.addEventListener('visibilitychange', async function(){
        if(document.hidden){ saveSitewideResume(); return; }
        if(isSitewideMode) claimPlaybackLeader(false);
        // 절전/백그라운드 복귀 직후에는 수동 전체송출 상태를 먼저 확정해야 합니다.
        // 방송 polling이 느린데 자동편성을 먼저 검사하면 잠깐 다른 곡이 재생될 수 있습니다.
        await loadSchedule();
        if(cfg.apiBroadcast) await loadBroadcast();
        checkDue();
    });
})();
