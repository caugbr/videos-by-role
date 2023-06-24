window.vbrProviders.vimeo = {
    name: "Vimeo",
    reUrl: /\bvimeo\.com\b/,
    reId: /.*[^0-9]?([0-9]{9})[^0-9]?.*/,
    getInfo(vid) {
        return new Promise(resolve => {
            const oembedUrl = 'https://vimeo.com/api/oembed.json?url=https%3A//vimeo.com';
            axios.get(`${oembedUrl}/${vid}`).then(res => resolve(res.data)).catch(() => resolve(false));
        });
    }
}