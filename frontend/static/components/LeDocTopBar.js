Vue.component('ledoc-top-bar', {
    props: [],
    template:
    '<Row>' +
    '    <i-col span="7" offset="1" style="text-align: left">\n' +
    '        <h1 style="color:#e4e5e7;margin: 20px 10px;cursor: default" v-on:click="onHomeLogo">Le Document</h1>\n' +
    '    </i-col>\n' +
    '    <i-col span="8">' +
    '        &nbsp;' +
    //'       <i-menu mode="horizontal" theme="dark" active-name="dir" style="background-color: transparent;margin-top: 10px;" @on-select="onMenuSelect">' +
    //'           <menu-item name="dir"><Icon type="folder"></Icon>文件夹</menu-item>' +
    //'           <menu-item name="user"><Icon type="ios-people"></Icon>用户列表</menu-item>' +
    //'       </i-menu>' +
    '    </i-col>\n' +
    '    <i-col span="7" style="margin: 25px 10px;text-align: right">' +
    '        <template v-if="iAmAdmin">' +
    '           <i-button type="text" icon="ios-people" style="color: #e4e5e7" @click="onUsers">用户列表</i-button>' +
    '           &nbsp;&nbsp;' +
    '        </template>' +
    '        <i-button type="text" icon="person" style="color: #e4e5e7" @click="onMyUserInfo">{{realname}}</i-button>' +
    '        &nbsp;&nbsp;' +
    '        <i-button type="text" icon="log-out" style="color: #e4e5e7" @click="onLogout">登出</i-button>\n' +
    '    </i-col>\n' +
    '</Row>',
    data: function () {
        return {
            iAmAdmin: false,
            realname: LeDoc.security.getRealname()
        }
    },
    methods: {
        onLogout: function () {
            LeDoc.security.logout();
        },
        onHomeLogo: function () {
            window.location.href = "index.html";
        },
        onUsers: function () {
            window.location.href = "users.html";
        },
        onMyUserInfo: function () {
            window.location.href = "user.html?username=" + encodeURIComponent(LeDoc.security.getUsername());
        },
        onMenuSelect: function (menu_name) {
            switch (menu_name) {
                case "dir":
                    window.location.href = "dir.html";
                    break;
                case 'user':
                    window.location.href = "users.html";
                    break;
                default:
                //do nothing
            }
        }
    },
    mounted: function () {
        LeDoc.security.thisPageRequestLogin();

        LeDoc.security.thisPageRequestPrivilege(
            LeDoc.security.privilegeOfAdmin,
            () => {
                this.iAmAdmin = true;
            },
            (privilege) => {
                // do nothing
            }
        )
    }
});