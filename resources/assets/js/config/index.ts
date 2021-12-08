import EventBus from "./EventBus";
import DeviceInfo from "./DeviceInfo";

export const EVENT_BUS = new EventBus();

export const DEVICE_INFO = DeviceInfo;
export const NODE_ENV = process.env.NODE_ENV || "prod";

var ua = navigator.userAgent.toLowerCase();
export const TX_ENV = !!(ua.match(/MicroMessenger\/[0-9]/i) || ua.match(/QQ\/[0-9]/i));

export const APP_NAME = (window as any).appName || "yingdaquan";
export const APP_NAME_CN = (window as any).appNameCN || "影大全";
export const APP_SLOGAN = (window as any).appSlogan || "影大全,你想看的都有";
export const APK_SCHEMA = (window as any).apkSchema || "yingdaquan";
export const APK_PACKAGE = (window as any).apkPackage || "com.yingdaquan";
export const APK_VERSION = (window as any).apkVersion || "4.1.0";
export const HOST_NAME = (window as any).appDomain || "yingdaquan.com";
export const GQL_URI = (window as any).gqlUri || "https://yingdaquan.com/gql";
export const DOWNLOAD_URL = (window as any).downloadUrl || "https://yingdaquan.com/app";
export const APK_URL = (window as any).apkUrl || "https://yingdaquan.com/apk/yingdaquan_4.1.0_1.apk";
export const LOGO_URL = (window as any).logoUrl || "/img/logo.png";
export const APP_LOGO_URL = (window as any).appLogoUrl || "/img/logo.png";
export const LOGO_TEXT_URL = (window as any).logoTextUrl || "/img/logo.png";
export const LOGO_ICON_URL = (window as any).logoIconUrl || "/img/logo.png";
