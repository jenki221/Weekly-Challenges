buster.spec.expose(); // Make some functions global

describe( "app model", function () {
    it( "exists", function () {
        expect( gotofritz.model.test() ).toBeType( "st" );
    });
});