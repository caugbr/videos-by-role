window.vbrProviders.youtube = {
    name: "YouTube",
    url: "//www.youtube.com",
    reUrl: /\b(youtube\.com|youtu\.be)\b/,
    reId: /.*v=([^&]+).*/,
    getInfo(vid) {
        return new Promise(resolve => {
            const oembedUrl = 'https://youtube.com/oembed?url=https%3A//www.youtube.com/watch?v=';
            axios.get(`${oembedUrl}${vid}&format=json`).then(res => resolve(res.data)).catch(() => resolve(false));
        });
    }
}