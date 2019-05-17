import lodash from "lodash";
import Toast from "izitoast";
import 'izitoast/dist/css/iziToast.css'

window._ = lodash;

Toast.settings({
    position: 'topRight',
})
window.Toast = Toast
