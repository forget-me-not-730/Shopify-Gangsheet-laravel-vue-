import {formatFonts, waitForWebfonts} from "@/Builder/Utils/helpers";

export default {
    beforeMount() {
        const fontsLoaded = []

        waitForWebfonts(this.$page.props.shopFonts, (font) => {
            if (font) {
                fontsLoaded.push(font)
                const [fonts, defaultFont] = formatFonts(fontsLoaded, this.$page.props.defaultFont)

                this.$store.commit('setStore', {path: 'shopFonts', value: fonts})
                this.$store.commit('setStore', {path: 'defaultFont', value: defaultFont})
            }
        }).then(() => {
            fabric.charWidthsCache = {}
            window._gangSheetCanvasEditor?.rerenderTextObjects()
        })

        this.$store.commit('setStore', {path: 'nameAndNumberFonts', value: this.$page.props.nameAndNumberFonts})
        waitForWebfonts(this.$page.props.nameAndNumberFonts).then(() => {
            fabric.charWidthsCache = {}
            window._gangSheetCanvasEditor?.rerenderNNObjects()
        });
    }
}
