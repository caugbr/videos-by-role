
window.vbrProviders = {};

function providerLinks() {
    let provs = [];
    for (const prov in vbrProviders) {
        if (vbrInfo.providers.includes(prov)) {
            provs.push(`<a href="${vbrProviders[prov].url}" target="_blank">${vbrProviders[prov].name}</a>`);
        }
    }
    return provs;
}

window.addEventListener('load', function() {
    // disable form submition while there is no selected category
    const submit = document.querySelector('input#publish');
    submit.disabled = true;
    const form = document.querySelector('form#post');
    form.addEventListener("submit", function(evt) {
        if (submit.disabled) {
            evt.preventDefault();
            return false;
        }
        return true;
    }, true);

    const label = this.document.querySelector('label[for="vbr_video"]');
    const links = providerLinks().join(', ');
    label.innerHTML = label.innerHTML.replace('%providers_list%', links);

    // get video info when user fill the video id or url
    const input = document.querySelector('input#vbr_video');
    const ratio_width = document.querySelector('input#video_width');
    const ratio_height = document.querySelector('input#video_height');
    let aspect_ratio = 0;
    const inputFn = function(evt) {
        evt.target.classList.remove('error');
        const url = evt.target.value;
        const vid = getVideoId(url);
        if (vid) {
            getVideoInfo(vid, url).then(info => {
                if (info === false) {
                    noVideo();
                } else {
                    showImageButton(info.thumbnail_url);
                    showSetTitleButton(info.title);
                    showAddButton(info.html);
                    ratio_width.value = info.width;
                    ratio_height.value = info.height;
                    aspect_ratio = info.height / info.width;
                    document.querySelector('.video-size').classList.remove('disabled');
                }
            });
        } else {
            noVideo();
        }
    };
    input.addEventListener('change', inputFn);
    if (input.value) {
        input.dispatchEvent(new Event('change'));
    }
    // force fields to preserve proportional size
    document.querySelector('input#video_size-responsive').addEventListener('click', function() {
        ratio_width.disabled = true;
        ratio_height.disabled = true;
    });
    document.querySelector('input#video_size-fixed').addEventListener('click', function() {
        ratio_width.disabled = false;
        ratio_height.disabled = false;
    });
    const rw_func = function() {
        const new_width = parseInt(ratio_width.value);
        const new_height = Math.round(new_width * aspect_ratio);
        ratio_height.value = new_height;
    };
    ratio_width.addEventListener('change', rw_func);
    ratio_width.addEventListener('keyup', rw_func);
    const rh_func = function() {
        const new_height = parseInt(ratio_height.value);
        const new_width = Math.round(new_height / aspect_ratio);
        ratio_width.value = new_width;
    };
    ratio_height.addEventListener('change', rh_func);
    ratio_height.addEventListener('keyup', rh_func);
    
    // select only one category
    const checkboxes = document.querySelectorAll('#video_categorychecklist input[type="checkbox"]');
    if (checkboxes.length == 0) {
        const li = this.document.createElement('li');
        li.innerHTML = 'You need to <a href="options-general.php?page=video-options">create some user levels</a> to be able to save this post.'
        document.querySelector('#video_categorychecklist').appendChild(li);
    }
    Array.from(checkboxes).forEach(cb => {
        cb.addEventListener('change', evt => {
            const show = evt.target.checked ? 'one' : 'all';
            Array.from(checkboxes).forEach(sibling => {
                if (sibling !== evt.target) {
                    sibling.closest('li').style.display = show == 'all' ? 'block' : 'none';
                }
            });
            evt.target.closest('li').style.display = 'block';
            submit.disabled = (show == 'all');
        });
    });
    // hide some things in category metabox
    document.querySelector('#video_category-adder').style.display = 'none';
    document.querySelector('#video_category-tabs li:last-child').style.display = 'none';
    // if editing, adjust box on page load
    const checkedCat = document.querySelector('#video_categorychecklist input[type="checkbox"]:checked');
    if (checkedCat) {
        checkedCat.dispatchEvent(new Event('change'));
    }
});

function getAspectRatio(width, height) {
    const aspectRatio = width / height;  
    if (isApproximatelyEqual(aspectRatio, 4 / 3)) {
        return '4:3';
    }
    if (isApproximatelyEqual(aspectRatio, 16 / 9)) {
        return '16:9';
    }
    if (isApproximatelyEqual(aspectRatio, 21 / 9)) {
        return '21:9';
    }
    return 'unknown';
}
  
function isApproximatelyEqual(a, b, epsilon = 0.05) {
    const diff = Math.abs(a - b);
    return diff <= epsilon;
}

function getVideoId(url) {
    for (const prov in vbrProviders) {
        if (vbrInfo.providers.includes(prov)) {
            if (vbrProviders[prov].reUrl.test(url)) {
                const matches = url.match(vbrProviders[prov].reId);
                return matches[1];
            }
        }
    }
    return false;
}

function getVideoInfo(vid, url) {
    for (const prov in vbrProviders) {
        if (vbrInfo.providers.includes(prov)) {
            if (vbrProviders[prov].reUrl.test(url)) {
                return vbrProviders[prov].getInfo(vid);
            }
        }
    }
}

function addResponsivity(embed) {
    const width = / width="([^"]+)"/.test(embed) ? embed.match(/ width="([^"]+)"/)[1] : 0;
    const height = / height="([^"]+)"/.test(embed) ? embed.match(/ height="([^"]+)"/)[1] : 0;
    let style = "";
    if (width && height) {
        const [w, h] = getAspectRatio(width, height).split(':').map(e => Number(e));
        style = ` style="--aspect-ratio: ${h / w};"`;
        // remove width and height from embed code
        embed = embed.replace(/ width="[^"]+"/, '');
        embed = embed.replace(/ height="[^"]+"/, '');
    }
    return `<div class="responsive-video"${style}>${embed}</div>`;
}

function addFixedSize(embed) {
    const width = document.querySelector('input#video_width').value;
    const height = document.querySelector('input#video_height').value;
    embed = embed.replace(/width="([^"]+)"/, `width="${width}"`);
    embed = embed.replace(/height="([^"]+)"/, `height="${height}"`);
    return embed;
}

function showAddButton(html) {
    console.log('showAddButton')
    const button = document.querySelector('button#add-video');
    const fn = function() {
        const responsive = document.querySelector('input#video_size-responsive').checked;
        const embed = responsive ? addResponsivity(html) : addFixedSize(html);
        console.log('atualizou')
        send_to_editor(embed);
    };
    try { button.removeEventListener('click', fn); } catch (e) {}
    button.addEventListener('click', fn);
    button.disabled = false;
}

function showImageButton(url) {
    if (UrlVars('post')) {
        const button = document.querySelector('button#use-image');
        const fn = function() {
            setThumbnail(url).then(response => {
                const thumbUrl = response.data.file;
                if (thumbUrl) {
                    const inside = document.querySelector('#postimagediv .inside');
                    let html = `<p><img src="${url}" style="width: 100%; height: auto;" /></p>`;
                    html += 'Save post or reload page to edit thumbnail in WP media window.'
                    inside.innerHTML = html;
                }
            });
        };
        try { button.removeEventListener('click', fn); } catch (e) {}
        button.addEventListener('click', fn);
        button.disabled = false;
    }  else {
        document.querySelector('.save-post-msg').style.display = 'block';
    }
}

function showSetTitleButton(title) {
    const button = document.querySelector('button#use-title');
    const fn = function() {
        if (title) {
            const titleInput = document.querySelector('input#title');
            titleInput.parentElement.querySelector('label').classList.add('screen-reader-text');
            titleInput.value = title;
        }
    };
    try { button.removeEventListener('click', fn); } catch (e) {}
    button.addEventListener('click', fn);
    button.disabled = false;
}
  
function setThumbnail(url) {
    return new Promise(resolve => {
        let data = new FormData();
        data.append('action', 'thumbnail_url');
        data.append('post_id', vbrInfo.post_id);
        data.append('nonce', vbrInfo.nonce);
        data.append('url', url + '.jpg');
        axios.post(vbrInfo.ajaxurl, data).then(response => {
            resolve(response);
        });
    });
}

function noVideo() {
    document.querySelector('button#use-title').disabled = true;
    document.querySelector('button#use-image').disabled = true;
    document.querySelector('button#add-video').disabled = true;
    document.querySelector('.video-size').classList.add('disabled');
    document.querySelector('.save-post-msg').style.display = 'none';
    const input = document.querySelector('input#vbr_video');
    if (input.value) {
        input.classList.add('error');
    }
}

function UrlVars(name = '') {
    let vars = {};
    const parts = decodeURIComponent(location.search).replace('?', '').split('&');
    parts.forEach(part => {
        const nv = part.split('=');
        vars[nv[0]] = nv[1];
    });
    return name ? vars[name] : vars;
}