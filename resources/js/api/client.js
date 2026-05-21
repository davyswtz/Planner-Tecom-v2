function getToken(){
    return localStorage.getItem('planner_token');
}

async function getUrl(url){
    const token = getToken();

    const response = await fetch('/api/' + url, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
        }
    })
    return response.json();
}

async function postUrl(url,data){
    const token = getToken();
    
    const response = await fetch('/api/' + url, {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data)
    })

    return response.json();
}

async function putUrl(url,data){
    const token = getToken();

    const response = await fetch('/api/' + url, {
        method: 'PUT',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data)
    })
    return response.json();
}

async function destroyUrl(url){
    const token = getToken();

    const response = await fetch('/api/' + url, {
        method: 'DELETE',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
        },
    })
    return response.json();
}

export { getToken, getUrl, postUrl, putUrl, destroyUrl };