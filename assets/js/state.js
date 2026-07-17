/* =====================================================
   FaltaUno — state.js
   Shared in-memory app state
   ===================================================== */

export const state = {
    user: null,
    filter: 'all',
    neighborhood: '',
    matches: [],
};

export const nav = {
    backTarget: 'home',
    detailId: null,
};

export const chatState = {
    matchId: null,
    lastId: 0,
    poll: null,
};
