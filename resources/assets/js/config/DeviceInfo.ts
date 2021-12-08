type OS = 'IOS' | 'Android' | 'PC';

interface Device {
  OS: OS;
}

const DeviceInfo: Device = {} as Device;

if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)) {
  //判断iPhone|iPad|iPod|iOS
  DeviceInfo.OS = 'IOS';
} else if (/(Android)/i.test(navigator.userAgent)) {
  //判断Android
  DeviceInfo.OS = 'Android';
} else {
  DeviceInfo.OS = 'PC';
}

export default DeviceInfo;
