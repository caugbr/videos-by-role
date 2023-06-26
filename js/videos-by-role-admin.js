
window.addEventListener('load', function() {
    const cat = document.querySelector('input#cat_name');
    const catSlug = document.querySelector('input#cat_slug');
    const rol = document.querySelector('input#role');
    const button = document.querySelector('input#submit');
    const names = getRoleNames();
    rol.addEventListener('keyup', function(evt) {
        if (!rol.value || names.includes(rol.value)) {
            catSlug.value = '';
            cat.value = '';
            button.disabled = true;
            if (rol.value) {
                rol.classList.add('error');
            }
            removeCurrentCap();
            return true;
        }
        button.disabled = false;
        rol.classList.remove('error');
        catSlug.value = slugify(`${rol.value}-videos`);
        cat.value = `${rol.value} videos`;
        addCapToList(rol.value);
    });
    const roleNames = document.querySelector('.existent-roles .role-names');
    roleNames.innerHTML = names.join(', ');
    if (names.length == 0) {
        roleNames.innerHTML = 'No levels yet';
    }

    const editButtons = document.querySelectorAll('.role .actions .edit-role');
    Array.from(editButtons).forEach(button => {
        button.addEventListener('click', function(evt) {
            const slug = evt.target.getAttribute('data-role');
            document.querySelector('#role_act').value = 'edit-role';
            document.querySelector('#role_slug').value = slug;
            document.querySelector('#role_form').submit();
        });
    });

    const removeButtons = document.querySelectorAll('.role .actions .remove-role');
    Array.from(removeButtons).forEach(button => {
        button.addEventListener('click', function(evt) {
            if (confirm('Remove this role?')) {
                const slug = evt.target.getAttribute('data-role');
                document.querySelector('#role_act').value = 'remove-role';
                document.querySelector('#role_slug').value = slug;
                document.querySelector('#role_form').submit();
            }
        });
    });

    document.querySelector('#edit-post-type').addEventListener('click', function(evt) {
        evt.preventDefault();
        evt.target.style.display = 'none';
        document.querySelector('.post-type-data').style.display = 'block';
    });
});

function removeCorsDomain(evt) {
    evt.preventDefault();
    const li = evt.target.closest('li');
    li.parentElement.removeChild(li);
}

function getRoleNames() {
    if (undefined === window.vbrRoles) {
        return false;
    }
    let names = [];
    for (const roleSlug in vbrRoles) {
        names.push(vbrRoles[roleSlug].name);
    }
    return names;
}

function removeCurrentCap() {
    const existent = document.querySelector('input.current-cap');
    if (existent) {
        const parentLi = existent.closest('li');
        parentLi.parentElement.removeChild(parentLi);
    }
}

function addCapToList(name, current = true, checked = true) {
    removeCurrentCap();

    const slug = slugify(`watch_${name}_videos`);

    const li = document.createElement('li');
    const label = document.createElement('label');
    const input = document.createElement('input');
    const span = document.createElement('span');

    input.id = `cap_${slug}`;
    input.name = 'capabilities[]';
    input.type = 'checkbox';
    input.value = slug;
    input.checked = !!checked;
    if (current) {
        input.className = 'current-cap';
        input.onclick = () => false;
    }

    span.innerHTML = slug;

    label.appendChild(input);
    label.appendChild(span);

    li.appendChild(label);

    const list = document.querySelector('ul.capabilities');
    if (list.firstChild) {
        list.insertBefore(li, list.firstChild);
    } else {
        list.appendChild(li);
    }
}

function slugify(string) {
    string = string.toLowerCase().trim().replace(/\s+/g, '-');
    const map = {
      'a': /[àáâãäå]/g,
      'e': /[èéêë]/g,
      'i': /[ìíîï]/g,
      'o': /[òóôõö]/g,
      'u': /[ùúûü]/g,
      'c': /[ç]/g,
      'n': /[ñ]/g
    };
    for (let char in map) {
      string = string.replace(map[char], char);
    }
    return string.replace(/[^a-z0-9-_]/g, '');
}