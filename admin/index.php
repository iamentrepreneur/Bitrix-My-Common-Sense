<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('Терапевты и Пациенты — фронт-панель');

global $USER;
if (!$USER->IsAuthorized() || !$USER->IsAdmin()) {
    LocalRedirect('/'); die();
}
$sess = bitrix_sessid();
?>

<div class="wrap" id="app" data-sess="<?=$sess?>">
    <h1>Терапевты и Пациенты — фронт-панель</h1>

    <div v-if="flash.message" class="msg" :class="flash.ok ? 'ok':'err'">{{ flash.message }}</div>

    <div class="card">
        <form @submit.prevent="reloadAll" class="row">
            <div class="col">
                <label>Поиск по ФИО/Email/Логину/Телефону/ID</label>
                <input v-model.trim="q" type="text" placeholder="Например: Иванов или mail@...">
            </div>
            <div class="col" style="align-self:flex-end">
                <button class="btn" type="submit">Искать</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div style="margin-bottom:8px" class="small">
            Найдено: терапевтов — {{ list.therapists.length }}, пациентов — {{ list.patients.length }}
        </div>

        <!-- Временный отладочный список -->
        <div v-if="list.therapists.length">
<!--            <div class="small" style="margin:4px 0 8px;">Первые 3 терапевта (debug):</div>-->
            <ul>
                <li v-for="u in list.therapists.slice(0,3)" :key="'dbg'+u.ID">
                    ID: {{u.ID}} — {{ u.LAST_NAME || '' }} {{ u.NAME || '' }} — {{ u.EMAIL }}
                </li>
            </ul>
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:8px 0 12px;">
        </div>

        <h2 style="margin:0 8px 8px 0;">Пользователи</h2>
        <div class="tabs">
            <div class="tab" :class="{active: tab==='therapists'}" @click="tab='therapists'">Терапевты</div>
            <div class="tab" :class="{active: tab==='patients'}"   @click="tab='patients'">Пациенты</div>
        </div>

        <div v-show="tab==='therapists'">
            <table class="table">
                <thead><tr><th>ID</th><th>ФИО</th><th>Email</th><th>Логин</th><th>Телефон</th><th>Статус</th><th>Действия</th></tr></thead>
                <tbody>
                <tr v-if="list.therapists.length===0"><td colspan="7">Пока пусто</td></tr>
                <tr v-for="u in list.therapists" :key="'t'+u.ID">
                    <td>{{u.ID}}</td>
                    <td>{{(u.LAST_NAME||'')+' '+(u.NAME||'')}}</td>
                    <td>{{u.EMAIL}}</td>
                    <td>{{u.LOGIN}}</td>
                    <td>{{u.PERSONAL_PHONE}}</td>
                    <td><span class="badge" :style="u.ACTIVE==='Y'?'':'background:#fee2e2;color:#991b1b'">{{u.ACTIVE==='Y'?'Активен':'Неактивен'}}</span></td>
                    <td>
                        <button class="btn link" @click="toggleActive(u)">{{u.ACTIVE==='Y'?'Деактивировать':'Активировать'}}</button> |
                        <button class="btn link" @click="rebind(u,'patient')">Сделать пациентом</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div v-show="tab==='patients'">
            <table class="table">
                <thead><tr><th>ID</th><th>ФИО</th><th>Email</th><th>Логин</th><th>Телефон</th><th>Статус</th><th>Действия</th></tr></thead>
                <tbody>
                <tr v-if="list.patients.length===0"><td colspan="7">Пока пусто</td></tr>
                <tr v-for="u in list.patients" :key="'p'+u.ID">
                    <td>{{u.ID}}</td>
                    <td>{{(u.LAST_NAME||'')+' '+(u.NAME||'')}}</td>
                    <td>{{u.EMAIL}}</td>
                    <td>{{u.LOGIN}}</td>
                    <td>{{u.PERSONAL_PHONE}}</td>
                    <td><span class="badge" :style="u.ACTIVE==='Y'?'':'background:#fee2e2;color:#991b1b'">{{u.ACTIVE==='Y'?'Активен':'Неактивен'}}</span></td>
                    <td>
                        <button class="btn link" @click="toggleActive(u)">{{u.ACTIVE==='Y'?'Деактивировать':'Активировать'}}</button> |
                        <button class="btn link" @click="rebind(u,'therapist')">Сделать терапевтом</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 style="margin:0 0 12px;">Создать пользователя</h2>
        <form @submit.prevent="createUser">
            <div class="row">
                <div class="col">
                    <label>Роль *</label>
                    <select v-model="create.role">
                        <option value="therapist">Терапевт</option>
                        <option value="patient">Пациент</option>
                    </select>
                </div>
                <div class="col">
                    <label>Логин *</label>
                    <input v-model.trim="create.login" required>
                </div>
                <div class="col">
                    <label>Email *</label>
                    <input v-model.trim="create.email" type="email" required>
                </div>
                <div class="col">
                    <label>Пароль *</label>
                    <input v-model="create.password" required>
                </div>
                <div class="col">
                    <label>Имя *</label>
                    <input v-model.trim="create.name" required>
                </div>
                <div class="col">
                    <label>Фамилия *</label>
                    <input v-model.trim="create.last_name" required>
                </div>
                <div class="col">
                    <label>Телефон</label>
                    <input v-model.trim="create.phone">
                </div>
                <div class="col">
                    <label>Активность</label>
                    <select v-model="create.active">
                        <option value="Y">Активен</option>
                        <option value="N">Неактивен</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:12px">
                <button class="btn" type="submit">Создать</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin:0 0 12px;">Привязать существующего пользователя к роли</h2>
        <form @submit.prevent="bindExisting">
            <div class="row">
                <div class="col">
                    <label>ID пользователя *</label>
                    <input v-model.number="bind.user_id" type="number" required>
                </div>
                <div class="col">
                    <label>Роль *</label>
                    <select v-model="bind.role">
                        <option value="therapist">Терапевт</option>
                        <option value="patient">Пациент</option>
                    </select>
                </div>
                <div class="col" style="align-self:flex-end">
                    <button class="btn" type="submit">Привязать</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
    (function () {
        const API  = '/local/api/therapy_users.php';
        const SESS = document.getElementById('app').dataset.sess;

        const app = Vue.createApp({
            data() {
                return {
                    q: '',
                    tab: 'therapists',
                    list: { therapists: [], patients: [] },
                    create: { role:'patient', login:'', email:'', password:'', name:'', last_name:'', phone:'', active:'Y' },
                    bind:   { user_id: null, role:'patient' },
                    flash:  { ok:false, message:'' },
                    busy: false
                };
            },
            mounted() {
                this.reloadAll();
            },
            methods: {
                async apiGet(params) {
                    const url = API + '?' + new URLSearchParams(params || {});
                    const r = await fetch(url, { credentials: 'same-origin' });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Ошибка запроса');
                    return data;
                },
                async apiPost(payload) {
                    const url = API + '?' + new URLSearchParams({ sessid: SESS });
                    const r = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Bitrix-Csrf-Token': SESS
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ ...(payload || {}), sessid: SESS })
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || data.success === false) throw new Error(data?.message || 'Ошибка запроса');
                    return data;
                },

                async reloadAll() {
                    try {
                        this.busy = true;
                        const data = await this.apiGet({ q: this.q });
                        console.log('API →', data);
                        const therapists = Array.isArray(data?.data?.therapists) ? data.data.therapists : [];
                        const patients   = Array.isArray(data?.data?.patients)   ? data.data.patients   : [];
                        // Принудительное присвоение полей — гарант реактивности
                        this.list.therapists = therapists;
                        this.list.patients   = patients;
                    } catch (e) {
                        this.fail(e.message);
                        this.list.therapists = [];
                        this.list.patients   = [];
                    } finally {
                        this.busy = false;
                    }
                },

                async createUser() {
                    try {
                        this.busy = true;
                        await this.apiPost({
                            action: 'create_user',
                            role: this.create.role,
                            login: this.create.login,
                            email: this.create.email,
                            password: this.create.password,
                            name: this.create.name,
                            last_name: this.create.last_name,
                            phone: this.create.phone,
                            active: this.create.active
                        });
                        this.ok('Пользователь создан');
                        this.create = { role:'patient', login:'', email:'', password:'', name:'', last_name:'', phone:'', active:'Y' };
                        await this.reloadAll();
                    } catch (e) {
                        this.fail(e.message);
                    } finally {
                        this.busy = false;
                    }
                },

                async bindExisting() {
                    try {
                        this.busy = true;
                        await this.apiPost({ action:'bind_existing', user_id: Number(this.bind.user_id), role: this.bind.role });
                        this.ok('Роль обновлена');
                        this.bind = { user_id: null, role:'patient' };
                        await this.reloadAll();
                    } catch (e) {
                        this.fail(e.message);
                    } finally {
                        this.busy = false;
                    }
                },

                async rebind(user, role) {
                    try {
                        this.busy = true;
                        await this.apiPost({ action:'bind_existing', user_id: Number(user.ID), role });
                        this.ok('Роль обновлена');
                        await this.reloadAll();
                    } catch (e) {
                        this.fail(e.message);
                    } finally {
                        this.busy = false;
                    }
                },

                async toggleActive(user) {
                    try {
                        this.busy = true;
                        const next = (user.ACTIVE === 'Y') ? 'N' : 'Y';
                        await this.apiPost({ action:'toggle_active', user_id: Number(user.ID), active: next });
                        this.ok('Активность обновлена');
                        await this.reloadAll();
                    } catch (e) {
                        this.fail(e.message);
                    } finally {
                        this.busy = false;
                    }
                },

                ok(msg)   { this.flash = { ok:true,  message: msg }; setTimeout(() => { this.flash.message = ''; }, 3000); },
                fail(msg) { this.flash = { ok:false, message: msg }; setTimeout(() => { this.flash.message = ''; }, 5000); }
            }
        });

        const vm = app.mount('#app');
        window.vmAdmin = vm; // для отладки: vmAdmin.list / vmAdmin.reloadAll()
    })();
</script>

<?php require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php'); ?>
