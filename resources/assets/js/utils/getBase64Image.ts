export const getBase64Image = (src: string) => {
    return new Promise((resolve, reject) => {
        const img = document.createElement('img');
        img.crossOrigin = 'Anonymous';
        img.src = src + '?v=' + Math.random();

        img.onload = () => {
            const canvas = document.createElement('canvas');
            const ratio = window.devicePixelRatio || 2;
            const width = img.width * ratio;
            const height = img.height * ratio;
            canvas.width = width;
            canvas.height = height;
            const ctx: any = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);
            // console.log(`canvas`, canvas);
            try {
                resolve(canvas.toDataURL('image/jpeg', 0.9)); // 这里是为了降低图片大小
            } catch (error) {
                console.log(`error`, error);
                reject(error);
            }
        };
    });
};
