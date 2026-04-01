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
        <div className="content"  id="home">
            <h2>            Welcome to Blue Horizon Cars – The Ultimate Luxury Car Showcase</h2>
            <p className="text">


            At Blue Horizon Cars, we celebrate the art of automotive excellence. Our showroom isn’t about sales—it’s about experiences. Every vehicle tells a story, every curve and contour reflects masterful design, engineering, and pure passion for luxury cars. From sleek supercars to sophisticated sedans, our collection represents the pinnacle of automotive craftsmanship and innovation.

            Step inside and explore a curated selection of the world’s most prestigious brands. Marvel at the aerodynamic lines of a Ferrari, the commanding presence of a Rolls-Royce, or the futuristic elegance of a Tesla Roadster. Each car is displayed to highlight its unique features, materials, and cutting-edge technology.

            Blue Horizon Cars is designed for enthusiasts, collectors, and dreamers alike. Whether you’re an automotive aficionado seeking inspiration, a designer admiring trends in vehicle aesthetics, or simply captivated by luxury, our showroom immerses you in a world where cars are more than machines—they are works of art.

            We host exclusive viewing events, virtual tours, and immersive multimedia experiences, allowing visitors to fully appreciate the beauty, power, and sophistication of each vehicle. Our knowledgeable staff are on hand to provide detailed insights, share stories about the history of the brands, and guide you through the craftsmanship that makes each car exceptional.

            At Blue Horizon Cars, we don’t sell cars—we celebrate them. Our mission is to create a space where automotive excellence can be admired, studied, and enjoyed. It’s a place to dream, to explore, and to experience the thrill of luxury automobiles in their purest form.

            Discover Blue Horizon Cars and let the finest vehicles inspire your imagination. Luxury, performance, and elegance await—without ever leaving the showroom.</p>
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

    async function likeCar() {
    const data = new FormData();
    data.append("id", car.id);

    const res = await fetch("./cars/like", {
        method: "POST",
        body: data
    });

    const json = await res.json();

    if (!json.success) {
        alert(json.message);
        return;
    }

    setCars(prev => prev.map(c => c.id === car.id ? json.data : c));
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
            <a href={`./cars/delete/${car.id}`} onClick = {delCar}>DELETE</a>
            <button onClick={likeCar}>❤️ ({Object.keys(car.likes || {}).length})
</button>

          {edit ?  <Edit setEdit={setEdit} car = {car} setCars={setCars}></Edit>: ""}
        </div>
    )
}

function Header() {




    
    return (
            
        <header>
            <h1>Blue Horizon Cars</h1>

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

        if(!data.success) return setMes("Not Admin");

        const {brand, model, price, image} = data.data;
        setEdit(prev=>!prev)

        setCars(prev=>{
            return prev.map(c=>{

                if(c.id != car.id) return c
                return {...c, brand, model, price, image }
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
        <input type="text" name="image" defaultValue={car.image} />
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



