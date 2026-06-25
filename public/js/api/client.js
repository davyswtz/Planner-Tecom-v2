function getToken(){
    return localStorage.getItem('planner_token');
}

async function parseJsonResponse(response) {
    return response.json().catch(() => ({}));
}

function extrairErro(payload, fallback = 'Erro na requisição.') {
    if (payload?.message) return payload.message;
    if (payload?.errors) return Object.values(payload.errors).flat().join(' ');
    return fallback;
}

async function getUrl(url){
    const token = getToken();

    const response = await fetch('/api/' + url, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
        },
        cache: 'no-store',
    });

    const payload = await parseJsonResponse(response);
    if (!response.ok) {
        throw new Error(extrairErro(payload));
    }

    return payload;
}

async function postUrl(url, data){
    const token = getToken();

    const response = await fetch('/api/' + url, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });

    const payload = await parseJsonResponse(response);
    if (!response.ok) {
        throw new Error(extrairErro(payload));
    }

    return payload;
}

async function putUrl(url, data){
    const token = getToken();

    const response = await fetch('/api/' + url, {
        method: 'PUT',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });

    const payload = await parseJsonResponse(response);
    if (!response.ok) {
        throw new Error(extrairErro(payload));
    }

    return payload;
}

async function destroyUrl(url){
    const token = getToken();

    const response = await fetch('/api/' + url, {
        method: 'DELETE',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
        },
        cache: 'no-store',
    });

    const payload = await parseJsonResponse(response);
    if (!response.ok) {
        throw new Error(extrairErro(payload));
    }

    return payload;
}

export { getToken, getUrl, postUrl, putUrl, destroyUrl };
