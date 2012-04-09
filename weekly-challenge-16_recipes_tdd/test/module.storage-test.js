buster.spec.expose(); // Make some functions global

describe( "storage module", function () {
    it( "exists", function () {
        expect( gotofritz.storage.test() ).toBeType( "string" );
    });
});