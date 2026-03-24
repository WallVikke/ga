ReactDOM.createRoot(document.querySelector("#app")).render(<App></App>)


function App() {

    return (
        <>
            <Header></Header>
            <Home></Home>

            <LoginForm></LoginForm>
            <RegisterForm></RegisterForm>
            <Cars></Cars>
            <Footer></Footer>


        </>


    );



}

function Home() {

    return (
        <div className="content" id="home">
            <h2>HOME</h2>
        </div>

    )

}

function Cars() {

    React.useEffect(() => {
        getCars();
    }, [])

    const [cars, setCars] = React.useState([])

    async function getCars() {

        let res = await fetch("http://localhost/vvga/cars/");
        let data = await res.json();
        setCars(data);
    }



    return (
        <div className="content" id="cars">
            <h2>Cars</h2>

            {cars.map(c => <Car setCars={setCars} car = {c} key = {c.id}></Car> )}
            <hr />
            <Create setCars={setCars}></Create>


        </div>

    )

}

function Car({car, setCars}) {


    async function delCar(event){
        event.preventDefault();
        console.log(event.target.href);

        const res = await fetch(event.target.href);
        const data = await res.json();

        console.log(data);

        if(!data.success) return alert("Not Admin");

        setCars(prev=> prev.filter(c => c.id != car.id))

    }

   

    const [edit, setEdit] = React.useState(false);

    console.log(edit);
    return (
        <div className="cars">
            <img src={car.image} alt={car.brand} />
            <h3>{car.brand}</h3>
            <p>{car.model}</p>
            <p>{car.price}</p>
            <button onClick={()=>setEdit(prev => !prev)}>EDIT</button>
            <a href={`./cars/delete/${car.id}`} onClick = {delCar}>Delete</a>

          {edit ?  <Edit setEdit={setEdit} car = {car} setCars={setCars}></Edit>: ""}
        </div>
    )
}


function Header() {
    return (

        <header>
            <h1>Viktors Gymnasiearbete</h1>

            <nav>
                <a href="http://localhost/vvga/#home">Home</a>
                <a href="http://localhost/vvga/#cars">Cars</a>
                <a href="http://localhost/vvga/#login-form">Logga in</a>
                <a href="http://localhost/vvga/#register-form">Registrera</a>
                <a href="http://localhost/vvga/logout">Logga ut</a>
            </nav>
        </header>


    );
};

function Create({ setCars }) {

    const [response, setResponse] = React.useState({});
    async function createCar(event) {
        event.preventDefault();  // stoppa vanlig händelse att skicka till server..

        const data = new FormData(event.target);

        const res = await fetch("./save", {
            method: "POST",
            body: data
        });
        const resData = await res.json();
        if (!resData.success) return alert("Not logged in as admin")
        setResponse(resData);
        setCars(prev => [...prev, resData.data])

    }


    return (

        <div id="create" className="">

            <h2>CREATE</h2>

            <form onSubmit={createCar} action="./save" method="post">
                <input type="text" name="brand" placeholder="Brand" />
                <input type="text" name="model" placeholder="Model" />
                <input type="text" name="price" placeholder="Price" />
                <input type="text" name="image" placeholder="Image URL" />
                <input type="submit" value="Create" />
            </form>

            {JSON.stringify(response)}
        </div>



    )



}


function LoginForm() {

    return (


        <div className="content" id="login-form">
            <h2>Logga in</h2>

            <form method="POST" action="http://localhost/vvga/login">
                <input type="text" name="username" placeholder="Användarnamn" required />
                <input type="password" name="password" placeholder="Lösenord" required />
                <button type="submit">Logga in</button>
            </form>

            <a href="http://localhost/vvga/register-form">Skapa konto</a>
        </div>

    )
}

function RegisterForm() {

    return (
        <div id="register-form" className="content">
            <h2>Skapa konto</h2>

            <form method="POST" action="http://localhost/vvga/register">
                <input type="text" name="username" placeholder="Användarnamn" required />
                <input type="password" name="password" placeholder="Lösenord" required />
                <button type="submit">Registrera</button>
            </form>

            <a href="http://localhost/vvga/login-form">Logga in</a>
        </div>
    )
}

function Edit({car, setCars, setEdit}){

    const [mes, setMes] = React.useState("");

    async function update(event) {
        event.preventDefault();

        const body = new FormData(event.target);

        const res = await fetch("./cars/update",{
            method:"POST",
            body

        })

        const data = await res.json();

        if(!data.success) return setMes("Error");

        const {brand, model, price} = data.data;
        setEdit(prev=>!prev)

        setCars(prev=>{
            return prev.map(c=>{

                if(c.id != car.id) return c
                return {...c, brand, model, price }
            })

        })
    }











  return(
    <div className="edit">
      <h2>Edit Car</h2>
      <form onSubmit={update} action="./cars/update" method="post">
        <input type="hidden" name="id" defaultValue={car.id} />
        <input type="text" name="brand" defaultValue={car.brand} />
        <input type="text" name="model" defaultValue={car.model} />
        <input type="text" name="price" defaultValue={car.price} />

        <input type="submit" value="Update" />
      </form>
        <h2>{mes}</h2>
    </div>
  )
}


function Footer() {
    return (
        <footer>
            © 2026 Viktor – Gymnasiearbete
        </footer>
    )
}



