window.vbrProviders.dailymotion = {
    name: "Dailymotion",
    url: "//www.dailymotion.com",
    reUrl: /\bdailymotion\.com\b/,
    reId: /.*\/video\/([^&\/]+).*/,
    getInfo(vid) {
        return new Promise(resolve => {
            const oembedUrl = 'https://www.dailymotion.com/oembed?url=https%3A//www.dailymotion.com/video';
            axios.defaults.headers.get['Access-Control-Allow-Origin'] = 'http://localhost/';
            axios.get(
                `${oembedUrl}/${vid}`).then(res => resolve(res.data), 
                { headers: { 'Access-Control-Allow-Origin': 'http://localhost/' } }
            ).catch(() => resolve(false));
        });
    }
}