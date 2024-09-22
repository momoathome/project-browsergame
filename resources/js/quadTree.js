export class Quadtree {
  constructor(x, y, width, height, capacity = 4) {
    this.boundary = { x, y, width, height };
    this.capacity = capacity;
    this.objects = [];
    this.divided = false;
    this.northwest = null;
    this.northeast = null;
    this.southwest = null;
    this.southeast = null;
  }

  insert(object) {
    if (!this.containsPoint(object.x, object.y)) {
      return false;
    }

    if (this.objects.length < this.capacity && !this.divided) {
      this.objects.push(object);
      return true;
    }

    if (!this.divided) {
      this.subdivide();
    }

    return (
      this.northwest.insert(object) ||
      this.northeast.insert(object) ||
      this.southwest.insert(object) ||
      this.southeast.insert(object)
    );
  }

  containsPoint(x, y) {
    return (
      x >= this.boundary.x &&
      x < this.boundary.x + this.boundary.width &&
      y >= this.boundary.y &&
      y < this.boundary.y + this.boundary.height
    );
  }

  subdivide() {
    const x = this.boundary.x;
    const y = this.boundary.y;
    const w = this.boundary.width / 2;
    const h = this.boundary.height / 2;

    this.northwest = new Quadtree(x, y, w, h, this.capacity);
    this.northeast = new Quadtree(x + w, y, w, h, this.capacity);
    this.southwest = new Quadtree(x, y + h, w, h, this.capacity);
    this.southeast = new Quadtree(x + w, y + h, w, h, this.capacity);

    this.divided = true;

    for (let object of this.objects) {
      this.insert(object);
    }
    this.objects = [];
  }

  query(range) {
    let found = [];

    if (!(
      range.x < this.boundary.x + this.boundary.width &&
      range.x + range.width > this.boundary.x &&
      range.y < this.boundary.y + this.boundary.height &&
      range.y + range.height > this.boundary.y
    )) {
      return found;
    }

    for (let object of this.objects) {
      if (
        object.x >= range.x &&
        object.x < range.x + range.width &&
        object.y >= range.y &&
        object.y < range.y + range.height
      ) {
        found.push(object);
      }
    }

    if (this.divided) {
      found = found.concat(
        this.northwest.query(range),
        this.northeast.query(range),
        this.southwest.query(range),
        this.southeast.query(range)
      );
    }

    return found;
  }
}
