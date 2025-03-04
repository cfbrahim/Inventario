create table  cliente
(
	id_cliente int auto_increment primary key,
    nombre varchar(35) not null,
    apellido1 varchar(35) not null ,
    apellido2 varchar(35) ,
    telefono char(12) ,
    email varchar(75) 
);

create table familia_prod 
(
	id_familia int auto_increment primary key,
    familia varchar(40) not null
);
create table productos 
(
	referencia char(3) primary key,
    nombre_producto varchar(50) not null,
    familia_producto int not null,
    precio double not null,
    cantidad int not null,
    constraint fk_familia foreign key (familia_producto) references familia_prod(id_familia) on delete cascade
);

create table compra 
(
	id_compra int auto_increment primary key,
    cliente int not null,
    ref_producto char(3) not null,
    cantidad_compra int not null,
	fecha_compra timestamp not null,
    constraint fk_com_cliente foreign key(cliente) references cliente(id_cliente),
	constraint fk_com_producto foreign key(ref_producto) references producto(referencia)
    
);