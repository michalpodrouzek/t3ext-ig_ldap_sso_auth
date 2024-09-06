import $ from 'jquery';

const IgLdapSsoAuthSearch = {
    type: 'fe_users',

    fields: {
        form: null,
        basedn: null,
        filter: null,
        result: null
    },

    initialize() {
        this.fields.form = $('#tx-igldapssoauth-searchform');
        this.fields.basedn = $('#tx-igldapssoauth-basedn');
        this.fields.filter = $('#tx-igldapssoauth-filter');
        this.fields.result = $('#tx-igldapssoauth-result');

        this.fields.form.submit((e) => {
            e.preventDefault(); // this will prevent from submitting the form
            this.search();
        });

        $(':radio').click((event) => {
            this.updateForm($(event.currentTarget).val());
        });

        $(':checkbox').click(() => {
            this.search();
        });

        $('#tx-igldapssoauth-search').click(() => {
            this.search();
        });

        if (this.fields.form.length) {
            this.search();
        }
    },

    updateForm(type) {
        this.type = type;

        $.ajax({
            url: TYPO3.settings.ajaxUrls['ldap_form_update'],
            data: {
                configuration: $('#tx-igldapssoauth-result').data('configuration'),
                type: type
            }
        }).done((data) => {
            if (data.success) {
                this.fields.basedn.val(data.configuration.basedn);
                this.fields.filter.val(data.configuration.filter);
                this.search();
            }
        });
    },

    search() {
        $.ajax({
            url: TYPO3.settings.ajaxUrls['ldap_search'],
            data: this.fields.form.serialize()
        }).done((data) => {
            this.fields.result.html(data.html);
        });
    }
};

$(document).ready(() => {
    IgLdapSsoAuthSearch.initialize();
});

export default IgLdapSsoAuthSearch;
