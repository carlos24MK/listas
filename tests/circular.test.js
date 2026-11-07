const {test} = require('node:test');
const assert = require('node:assert/strict');
const {Persona, ListaEnlazada, ListaDobleCircular} = require('../circular/lista.js');
const {ListaEnlazada: ListaSimple} = require('../simple/lista.js');
const persona = id => new Persona(String(id), 'Ana', 'Torres', '2005-04-12', 'ana@ejemplo.com');
function comprobar(l, ids) {
    assert.equal(l.cantidad, ids.length);
    if (!ids.length) { assert.equal(l.cabeza, null); assert.equal(l.cola, null); return; }
    assert.equal(l.cabeza.antes, l.cola);
    assert.equal(l.cola.siguiente, l.cabeza);
    let n = l.cabeza;
    for (const id of ids) {
        assert.equal(n.dato.identificacion, String(id));
        assert.equal(n.siguiente.antes, n);
        assert.equal(n.antes.siguiente, n);
        n = n.siguiente;
    }
    assert.equal(n, l.cabeza);
    n = l.cola;
    for (const id of [...ids].reverse()) { assert.equal(n.dato.identificacion, String(id)); n=n.antes; }
    assert.equal(n,l.cola);
    const recorrido=[];l.recorrer(n=>recorrido.push(n.dato.identificacion));
    assert.deepEqual(recorrido,ids.map(String));
}
test('vacía y único nodo: cierre en ambos sentidos',()=>{
 const l=new ListaDobleCircular();comprobar(l,[]);assert.equal(l.buscar('9'),null);
 const n=l.agregar(persona(1));comprobar(l,[1]);assert.equal(n.antes,n);assert.equal(n.siguiente,n);
 l.eliminar('1');comprobar(l,[]);assert.equal(n.antes,null);assert.equal(n.siguiente,null);
 l.agregar(persona(2));comprobar(l,[2]);
});
test('insertar al principio, en medio y al final conserva los nodos',()=>{
 const l=new ListaDobleCircular();const n=l.agregar(persona(2));l.agregar(persona(4));l.agregar(persona(1),1);l.agregar(persona(3),3);
 comprobar(l,[1,2,3,4]);assert.equal(l.buscar(2),n);
});
test('eliminar cabeza, cola, intermedio, dos y único',()=>{
 const l=new ListaDobleCircular();for(let i=1;i<=5;i++)l.agregar(persona(i));
 for(const [id,ids] of [[3,[1,2,4,5]],[1,[2,4,5]],[5,[2,4]],[2,[4]],[4,[]]]){l.eliminar(id);comprobar(l,ids);}
});
test('búsqueda ausente termina y edición conserva nodo, ID y enlaces',()=>{
 const l=new ListaDobleCircular();l.agregar(persona(1));const n=l.agregar(persona(2));
 assert.equal(l.buscar('999'),null);assert.equal(l.consultar('999'),null);
 const before=n.antes,after=n.siguiente;l.editar('2',{...persona(9),nombres:'Pedro'});
 assert.equal(l.buscar('2'),n);assert.equal(n.dato.nombres,'Pedro');assert.equal(n.antes,before);assert.equal(n.siguiente,after);comprobar(l,[1,2]);
});
test('operaciones inválidas no rompen el círculo',()=>{
 const l=new ListaDobleCircular();l.agregar(persona(1));
 for(const pos of [0,-1,3,1.5,'1']){assert.throws(()=>l.agregar(persona(2),pos));comprobar(l,[1]);}
 assert.throws(()=>l.agregar(persona(1)));assert.throws(()=>l.eliminar('999'));comprobar(l,[1]);
});
test('secuencia de inserciones y eliminaciones verifica cada cierre',()=>{
 const l=new ListaDobleCircular(),ids=[];
 for(let i=1;i<=100;i++){let pos=(i*17)%(ids.length+1);l.agregar(persona(i),pos+1);ids.splice(pos,0,i);comprobar(l,ids);if(i%3===0){pos=(i*7)%ids.length;l.eliminar(ids[pos]);ids.splice(pos,1);comprobar(l,ids);}}
 l.vaciar();comprobar(l,[]);
});
test('las versiones lineales siguen terminando en null',()=>{
 const doble=new ListaEnlazada();doble.agregar(persona(1));assert.equal(doble.cabeza.antes,null);assert.equal(doble.cola.siguiente,null);
 const simple=new ListaSimple(); // Usa su propia clase Persona.
 const P=require('../simple/lista.js').Persona;simple.agregar(new P('1','Ana','Torres','2005-04-12','ana@ejemplo.com'));
 assert.equal(simple.cola.siguiente,null);assert.ok(!('antes' in simple.cabeza));
});

test('guardar y recuperar el círculo sin perder el cierre ni aceptar ciclos incompletos',()=>{
 const vm=require('node:vm'),fs=require('node:fs'),path=require('node:path');
 const root=path.join(__dirname,'..');
 let saved='';
 const sandbox=vm.createContext({document:{getElementById:()=>({textContent:''})},localStorage:{setItem:(_key,text)=>{saved=text;}}});
 vm.runInContext(fs.readFileSync(path.join(root,'lista.js'),'utf8'),sandbox);
 vm.runInContext(fs.readFileSync(path.join(root,'circular/lista.js'),'utf8'),sandbox);
 const app=fs.readFileSync(path.join(root,'circular/app.js'),'utf8').split('$("form-persona").addEventListener("submit"')[0];
 vm.runInContext(app,sandbox);
 vm.runInContext('cargarEjemplo(); guardarLocal(); lista.vaciar();',sandbox);
 sandbox.snapshot=saved;
 vm.runInContext('recuperarLocal(snapshot);',sandbox);
 assert.equal(vm.runInContext('lista.cantidad',sandbox),3);
 assert.equal(vm.runInContext('lista.cola.siguiente === lista.cabeza && lista.cabeza.antes === lista.cola',sandbox),true);
 const invalid=JSON.parse(saved);invalid.nodos['303'].siguiente='202';sandbox.snapshot=JSON.stringify(invalid);
 assert.throws(()=>vm.runInContext('recuperarLocal(snapshot)',sandbox));
 assert.equal(vm.runInContext('lista.cantidad',sandbox),3);
 vm.runInContext('lista.vaciar(); guardarLocal();',sandbox);sandbox.snapshot=saved;
 vm.runInContext('recuperarLocal(snapshot)',sandbox);assert.equal(vm.runInContext('lista.cabeza',sandbox),null);
});
