Vue.component('ledoc-buttom-bar', {
    props: [],
    data: function () {
        return {
            copyright: '',
        }
    },
    template: '<Row style="text-align: center;padding-top:5px;border-top:1px solid lavender">' +
    '                <i-col span="20" offset="2">' +
    '                    <p>{{copyright}}</p>' +
    '                    <p>Powered by <a href="https://github.com/sinri/LeDoc">LeDoc</a>, Copyright 2018 Sinri Edogawa</p>\n' +
    '                </i-col>\n' +
    '            </Row>',
    methods: {
        loadCopyrightInfo: function () {
            LeDoc.api.call(
                "SiteController/getCopyrightInfo",
                {},
                (data) => {
                    this.copyright = data.copyright;
                }
            )
        }
    },
    mounted: function () {
        this.loadCopyrightInfo();
    }
});