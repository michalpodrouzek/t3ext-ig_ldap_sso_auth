import $ from 'jquery';

class IgLdapSsoAuthImport {
    constructor() {
        this.fields = {
            form: null
        };
    }

    initialize() {
        this.fields.form = $('#tx-igldapssoauth-importform');

        $('button').on('click', (event) => {
            $('#tx-igldapssoauth-dn').val($(event.target).val());
        });

        this.fields.form.on('submit', (e) => {
            e.preventDefault(); // this will prevent form submission
            let dn = $('#tx-igldapssoauth-dn').val();
            dn = dn.replace('\\', '\\\\');
            this.ldapImport($(`button[value='${dn}']`).closest('tr'));
        });
    }

    ldapImport(row) {
        const action = this.fields.form.data('ajaxaction');

        // Deactivate the button
        row.find('button').prop('disabled', true);

        $.ajax({
            url: TYPO3.settings.ajaxUrls[action],
            data: this.fields.form.serialize()
        }).done((data) => {
            if (data.success) {
                row.removeClass().addClass('local-ldap-user-or-group');
                row.find('td.col-icon span').prop('title', 'id=' + data.id);
                row.find('td').removeClass('future-value');
                row.find('button').hide(400, 'linear');
            } else {
                row.find('button').prop('disabled', false);
                alert(data.message);
            }
        });
    }
}

// Initialize the module when the document is ready
$(document).ready(() => {
    const igLdapSsoAuthImport = new IgLdapSsoAuthImport();
    igLdapSsoAuthImport.initialize();
});

export default IgLdapSsoAuthImport;
