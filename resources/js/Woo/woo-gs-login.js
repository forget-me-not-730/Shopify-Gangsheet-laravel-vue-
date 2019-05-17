if (window.opener) {
    const customer_id = Number(window.GangSheetOptions.customer?.id)
    const customer_email = window.GangSheetOptions.customer?.email
    if (customer_id || customer_email) {
        window.opener.postMessage({
            action: 'customer-login',
            customer_id: customer_id,
            customer_email: customer_email
        }, '*')
        window.close()
    }
}
