let LeDoc = {
    security: {
        getToken: () => {
            return Cookies.get('ledoc_token');
        },
        getUsername: () => {
            return Cookies.get("username");
        },
        getRealname: () => {
            return Cookies.get("realname");
        },
        setToken: (token, expireTimestamp, username, realname) => {
            console.log('expireTimestamp', expireTimestamp, new Date(expireTimestamp * 1000));
            Cookies.set('ledoc_token', token, {expires: new Date(expireTimestamp * 1000)});
            Cookies.set('username', username, {expires: new Date(expireTimestamp * 1000)});
            Cookies.set('realname', realname, {expires: new Date(expireTimestamp * 1000)});
        },
        cleanToken: () => {
            Cookies.remove('ledoc_token');
            Cookies.remove('username');
            Cookies.remove('realname');
        },
        logout: () => {
            LeDoc.security.cleanToken();
            window.location.href = "login.html";
        },
        thisPageRequestLogin: () => {
            console.log("thisPageRequestLogin, go checking", LeDoc.security.getToken());
            if (!LeDoc.security.getToken()) {
                window.location.replace("login.html");
            }
        },
        privilegeOfAdmin: 'PRIVILEGE_ADMIN',
        thisPageRequestPrivilege: (privilege, callbackWhenPermitted, callbackWhenNotPermitted) => {
            LeDoc.api.call(
                "SecurityController/currentUserInfo",
                {},
                (data) => {
                    if (data.privileges && data.privileges.indexOf(privilege) >= 0) {
                        if (callbackWhenPermitted) callbackWhenPermitted();
                    } else {
                        //alert("没有权限："+privilege);
                        if (callbackWhenNotPermitted) callbackWhenNotPermitted(privilege);
                    }
                },
                (error, code) => {
                    //alert("无法认证权限："+privilege+" 错误："+error+" 请求回应代码："+code);
                    if (callbackWhenNotPermitted) callbackWhenNotPermitted(privilege);
                }
            )
        },
    },
    api: {
        apiBaseUrl: "../api/",
        /**
         *
         * @param apiPath a string
         * @param data an object to package
         * @param callbackForData (data)=>{}
         * @param callbackForError (error,status)=>{}
         */
        call: (apiPath, data, callbackForData, callbackForError) => {
            if (!data) {
                data = {};
            }
            data.token = LeDoc.security.getToken();
            axios.post(LeDoc.api.apiBaseUrl + apiPath, data)
                .then((response) => {
                    console.log("then", response);
                    if (response.status !== 200 || !response.data) {
                        callbackForError(response.data, response.status);
                        return;
                    }
                    let body = response.data;
                    if (body.code && body.code === 'OK') {
                        console.log("success with data", body.data);
                        callbackForData(body.data);
                        return;
                    }
                    callbackForError((body.data ? body.data : 'Unknown Error'), response.status);
                })
                .catch((error) => {
                    console.log("catch", error);
                    callbackForError(error, -1);
                })
        }
    },
    page: {
        getParameterByName: function (name, defaultValue) {
            let match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
            let v = match && decodeURIComponent(match[1].replace(/\+/g, ' '));
            return v ? v : defaultValue;
        }
    },
    ui: {
        showTopAlert: function (vueInstance, error) {
            vueInstance.$Message.error({
                content: error,
                duration: 0,
                closable: true
            })
        }
    },
    data: {
        copy: function (object) {
            return JSON.parse(JSON.stringify(object));
        }
    }
};