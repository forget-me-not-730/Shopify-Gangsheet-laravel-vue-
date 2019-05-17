import {getCanvaAuthToken} from "@/Builder/Apis/builderApi";

export default {
    mounted() {
        let timer

        const userAgent = navigator.userAgent;

        const patterns = {
            mobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i,
            ipad: /iPad/i,
            tablet: /iPad|Android/i,
            desktop: /Win|Mac|Linux/i
        };

        if (!patterns.mobile.test(userAgent) && patterns.desktop.test(userAgent)) {
            window.addEventListener('resize', () => {
                if (timer) {
                    clearTimeout(timer)
                }
                timer = setTimeout(async () => {
                    await this.forceRerender()
                }, 1000)
            })
        }

        getCanvaAuthToken({
            user_id: this.shop.id,
            customer_id: this.customer?.id ?? ''
        }).then(res => {
            if (res) {
                this.canva = {
                    loading: false,
                    authorize_url: res.authorize_url,
                    access_token: res.access_token,
                    name: res.name,
                }
            }
        })
    },
    unmounted() {
        this.workingDesignIndex = 0
        this.workingDesigns = []
        this.gangSheetsPreview = false
    }
}
