function apiGet(url) {
  return fetch(url).then(function(r) {
    if (!r.ok) throw new Error('GET ' + url + ' failed: ' + r.status);
    return r.json();
  });
}
function apiPost(url, data) {
  return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }).then(function(r) {
    if (!r.ok) throw new Error('POST ' + url + ' failed: ' + r.status);
    return r.json();
  });
}
function apiPut(url, data) {
  return fetch(url, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }).then(function(r) {
    if (!r.ok) throw new Error('PUT ' + url + ' failed: ' + r.status);
    return r.json();
  });
}
function apiDelete(url) {
  return fetch(url, { method: 'DELETE' }).then(function(r) {
    if (!r.ok) throw new Error('DELETE ' + url + ' failed: ' + r.status);
    return r.json();
  });
}
