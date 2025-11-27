// src/main.ts
import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue"; // chuẩn
import router from "./router"; // chuẩn
import "../assets/main.css";
const app = createApp(App);

const pinia = createPinia();
app.use(pinia); // 👈 PHẢI CÓ DÒNG NÀY
app.use(router);

app.mount("#app");
